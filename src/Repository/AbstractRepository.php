<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\Common\Exceptions\Missing404Exception as ElasticMissing404Exception;
use Generator;
use Kununu\Elasticsearch\Exception\BulkException;
use Kununu\Elasticsearch\Exception\DeleteException;
use Kununu\Elasticsearch\Exception\DocumentNotFoundException;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Exception\RepositoryException;
use Kununu\Elasticsearch\Exception\UpdateException;
use Kununu\Elasticsearch\Exception\UpsertException;
use Kununu\Elasticsearch\Exception\WriteOperationException;
use Kununu\Elasticsearch\Query\CompositeAggregationQueryInterface;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\AggregationResultSet;
use Kununu\Elasticsearch\Result\AggregationResultSetInterface;
use Kununu\Elasticsearch\Result\CompositeResult;
use Kununu\Elasticsearch\Result\ResultIterator;
use Kununu\Elasticsearch\Result\ResultIteratorInterface;
use Kununu\Elasticsearch\Util\LogErrorTrait;
use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\Common\Exceptions\Missing404Exception as OpenSearchMissing404Exception;
use Psr\Log\LoggerAwareInterface;
use Throwable;

abstract class AbstractRepository implements RepositoryInterface, LoggerAwareInterface
{
    use LogErrorTrait;
    use LoggerAwareTrait;

    protected const string EXCEPTION_PREFIX = '';

    protected readonly RepositoryConfiguration $config;
    protected readonly ?string $entityClass;
    protected readonly ?EntityFactoryInterface $entityFactory;

    public function __construct(protected readonly ElasticsearchClient|OpenSearchClient $client, array $config)
    {
        $this->config = new RepositoryConfiguration($config);
        $this->entityClass = $this->config->getEntityClass();
        $this->entityFactory = $this->config->getEntityFactory();
    }

    public function save(string $id, array|object $entity): void
    {
        $document = $this->prepareDocument($entity);

        try {
            $this->client->index(
                array_merge($this->buildRequestBase(OperationType::WRITE), ['id' => $id, 'body' => $document])
            );

            $this->postSave($id, $document);
        } catch (Throwable $t) {
            $this->logError($t);

            throw new UpsertException(
                message: $t->getMessage(),
                previous: $t,
                documentId: $id,
                document: $document,
                prefix: static::EXCEPTION_PREFIX
            );
        }
    }

    public function saveBulk(array $entities): void
    {
        $body = [];
        foreach ($entities as $id => $entity) {
            $body[] = ['index' => ['_id' => $id]];
            $body[] = $this->prepareDocument($entity);
        }

        try {
            $this->client->bulk(
                array_merge($this->buildRequestBase(OperationType::WRITE), ['body' => $body])
            );

            $this->postSaveBulk($entities);
        } catch (Throwable $t) {
            $this->logError($t);

            throw new BulkException(
                message: $t->getMessage(),
                previous: $t,
                operations: $body,
                prefix: static::EXCEPTION_PREFIX
            );
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->client->delete(
                array_merge($this->buildRequestBase(OperationType::WRITE), ['id' => $id])
            );

            $this->postDelete($id);
        } catch (ElasticMissing404Exception|OpenSearchMissing404Exception $e) {
            throw new DocumentNotFoundException(
                id: $id,
                previous: $e,
                prefix: static::EXCEPTION_PREFIX
            );
        } catch (Throwable $t) {
            $this->logError($t);

            throw new DeleteException(
                message: $t->getMessage(),
                previous: $t,
                documentId: $id,
                prefix: static::EXCEPTION_PREFIX
            );
        }
    }

    public function deleteByQuery(QueryInterface $query, bool $proceedOnConflicts = false): array
    {
        return $this->executeWrite(
            function() use ($query, $proceedOnConflicts): array {
                $rawQuery = $this->buildRawQuery($query, OperationType::WRITE);
                if ($proceedOnConflicts) {
                    $rawQuery['conflicts'] = 'proceed';
                }

                return $this->client->deleteByQuery($rawQuery);
            }
        );
    }

    public function deleteBulk(string ...$ids): void
    {
        if (empty($ids)) {
            return;
        }

        $body = [];
        foreach ($ids as $id) {
            $body[] = ['delete' => ['_id' => $id]];
        }

        try {
            $this->client->bulk(
                array_merge($this->buildRequestBase(OperationType::WRITE), ['body' => $body])
            );

            $this->postDeleteBulk(...$ids);
        } catch (Throwable $t) {
            $this->logError($t);

            throw new BulkException(
                message: $t->getMessage(),
                previous: $t,
                operations: $body,
                prefix: static::EXCEPTION_PREFIX
            );
        }
    }

    public function findByQuery(QueryInterface $query): ResultIteratorInterface
    {
        return $this->executeRead(
            fn(): ResultIteratorInterface => $this->parseRawSearchResponse(
                $this->client->search($this->buildRawQuery($query, OperationType::READ))
            )
        );
    }

    public function findScrollableByQuery(
        QueryInterface $query,
        ?string $scrollContextKeepalive = null,
    ): ResultIteratorInterface {
        return $this->executeRead(
            function() use ($query, $scrollContextKeepalive): ResultIteratorInterface {
                $rawQuery = $this->buildRawQuery($query, OperationType::READ);
                $rawQuery['scroll'] = $scrollContextKeepalive ?: $this->config->getScrollContextKeepalive();

                return $this->parseRawSearchResponse(
                    $this->client->search($rawQuery)
                );
            }
        );
    }

    public function findByScrollId(
        string $scrollId,
        ?string $scrollContextKeepalive = null,
    ): ResultIteratorInterface {
        return $this->executeRead(
            fn(): ResultIteratorInterface => $this->parseRawSearchResponse(
                $this->client->scroll([
                    'body'   => [
                        'scroll_id' => $scrollId,
                    ],
                    'scroll' => $scrollContextKeepalive ?: $this->config->getScrollContextKeepalive(),
                ])
            )
        );
    }

    public function clearScrollId(string $scrollId): void
    {
        $this->execute(
            fn(): array => $this->client->clearScroll([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ]),
            ''
        );
    }

    public function findById(string $id, array $sourceFields = []): array|object|null
    {
        return $this->executeRead(
            function() use ($id, $sourceFields): object|array|null {
                try {
                    $response = $this->client->get(
                        array_merge(
                            $this->buildRequestBase(OperationType::READ),
                            ['id' => $id],
                            empty($sourceFields) ? [] : ['_source' => $sourceFields]
                        )
                    );

                    if (!($response['found'] ?? false)) {
                        throw match (true) {
                            $this->client instanceof OpenSearchClient => new OpenSearchMissing404Exception(),
                            default                                   => new ElasticMissing404Exception(),
                        };
                    }
                } catch (ElasticMissing404Exception|OpenSearchMissing404Exception) {
                    return null;
                }

                return $this->createPotentialEntity($response);
            }
        );
    }

    public function findByIds(array $ids, array $sourceFields = []): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->executeRead(
            function() use ($ids, $sourceFields): array {
                $requestBody = array_merge(
                    $this->buildRequestBase(OperationType::READ),
                    ['body' => $this->getBodyForMultipleGet($ids, $sourceFields)],
                );

                try {
                    $docs = $this->client->mget($requestBody);
                } catch (Throwable $t) {
                    $this->logCritical('Request error', ['request' => json_encode($requestBody)]);

                    throw $t;
                }

                $docs = array_filter($docs['docs'] ?? [], static fn(array $v): bool => $v['found'] ?? false);

                if ($this->entityClass || $this->entityFactory) {
                    return array_map(
                        fn(array $doc): array|object => $this->createPotentialEntity($doc),
                        $docs
                    );
                }

                return $docs;
            }
        );
    }

    public function count(): int
    {
        return $this->countByQuery(Query::create());
    }

    public function countByQuery(QueryInterface $query): int
    {
        return $this->executeRead(
            fn(): int => $this->client->count($this->buildRawQuery($query, OperationType::READ))['count']
        );
    }

    public function aggregateByQuery(QueryInterface $query): AggregationResultSetInterface
    {
        return $this->executeRead(
            function() use ($query): AggregationResultSetInterface {
                $result = $this->client->search(
                    $this->buildRawQuery($query, OperationType::READ)
                );

                return AggregationResultSet::create($result['aggregations'] ?? [])
                    ->setDocuments($this->parseRawSearchResponse($result));
            }
        );
    }

    /**  @return Generator<CompositeResult> */
    public function aggregateCompositeByQuery(CompositeAggregationQueryInterface $query): Generator
    {
        $afterKey = null;

        do {
            $result = $this
                ->aggregateByQuery($query->withAfterKey($afterKey)->getQuery())
                ->getResultByName($query->getName());

            foreach ($result?->getFields()['buckets'] ?? [] as $bucket) {
                if (!empty($bucket['key']) && !empty($bucket['doc_count'])) {
                    yield new CompositeResult(
                        $bucket['key'],
                        $bucket['doc_count'],
                        $query->getName()
                    );
                }
            }

            $afterKey = $result?->get('after_key') ?? null;
        } while (null !== $afterKey);
    }

    public function updateByQuery(QueryInterface $query, array $updateScript): array
    {
        return $this->executeWrite(
            function() use ($query, $updateScript): array {
                $rawQuery = $this->buildRawQuery($query, OperationType::WRITE);
                $rawQuery['body']['script'] = $this->sanitizeUpdateScript($updateScript)['script'];

                return $this->client->updateByQuery($rawQuery);
            }
        );
    }

    public function upsert(string $id, array|object $entity): void
    {
        $document = $this->prepareDocument($entity);

        try {
            $this->client->update(
                array_merge(
                    $this->buildRequestBase(OperationType::WRITE),
                    ['id' => $id, 'body' => ['doc' => $document, 'doc_as_upsert' => true]]
                )
            );

            $this->postUpsert($id, $document);
        } catch (Throwable $t) {
            $this->logError($t);

            throw new UpsertException(
                message: $t->getMessage(),
                previous: $t,
                documentId: $id,
                document: $document,
                prefix: static::EXCEPTION_PREFIX
            );
        }
    }

    public function update(string $id, array|object $partialEntity): void
    {
        $document = $this->prepareDocument($partialEntity);

        try {
            $this->client->update(
                array_merge(
                    $this->buildRequestBase(OperationType::WRITE),
                    ['id' => $id, 'body' => ['doc' => $document]]
                )
            );

            $this->postUpdate($id, $document);
        } catch (Throwable $t) {
            $this->logError($t);

            throw new UpdateException(
                message: $t->getMessage(),
                previous: $t,
                documentId: $id,
                document: $document,
                prefix: static::EXCEPTION_PREFIX
            );
        }
    }

    protected function postSave(string $id, array $document): void
    {
        // ready to be overwritten :)
    }

    protected function postSaveBulk(array $entities): void
    {
        // ready to be overwritten :)
    }

    protected function postDelete(string $id): void
    {
        // ready to be overwritten :)
    }

    protected function postDeleteBulk(string ...$ids): void
    {
        // ready to be overwritten :)
    }

    protected function postUpsert(string $id, array $document): void
    {
        // ready to be overwritten :)
    }

    protected function postUpdate(string $id, array $document): void
    {
        // ready to be overwritten :)
    }

    protected function executeRead(callable $operation): mixed
    {
        return $this->execute($operation, OperationType::READ);
    }

    protected function executeWrite(callable $operation): mixed
    {
        return $this->execute($operation, OperationType::WRITE);
    }

    protected function execute(callable $operation, string $operationType): mixed
    {
        try {
            return $operation();
        } catch (Throwable $t) {
            $this->logError($t);

            throw match ($operationType) {
                OperationType::READ  => new ReadOperationException(
                    message: $t->getMessage(),
                    previous: $t,
                    prefix: static::EXCEPTION_PREFIX
                ),
                OperationType::WRITE => new WriteOperationException(
                    message: $t->getMessage(),
                    previous: $t,
                    prefix: static::EXCEPTION_PREFIX
                ),
                default              => new RepositoryException(
                    message: $t->getMessage(),
                    previous: $t,
                    prefix: static::EXCEPTION_PREFIX
                ),
            };
        }
    }

    protected function buildRequestBase(string $operationType): array
    {
        $base = [
            'index' => $this->config->getIndex($operationType),
        ];

        if ($operationType === OperationType::WRITE && $this->config->getForceRefreshOnWrite()) {
            $base['refresh'] = true;
        }

        if ($operationType === OperationType::READ && null !== $this->config->getTrackTotalHits()) {
            $base['track_total_hits'] = $this->config->getTrackTotalHits();
        }

        return $base;
    }

    protected function buildRawQuery(QueryInterface $query, string $operationType): array
    {
        return array_merge(
            $this->buildRequestBase($operationType),
            ['body' => $query->toArray()]
        );
    }

    protected function parseRawSearchResponse(array $rawResult): ResultIteratorInterface
    {
        $results = array_map(
            fn(array $hit) => $this->createPotentialEntity($hit),
            $rawResult['hits']['hits'] ?? []
        );

        return ResultIterator::create($results)
            ->setTotal($rawResult['hits']['total']['value'] ?? 0)
            ->setScrollId($rawResult['_scroll_id'] ?? null);
    }

    protected function splitSourceAndMetaData(array $hit): array
    {
        $metaData = $hit;
        unset($metaData['_source']);

        return ['source' => $hit['_source'], 'meta' => $metaData];
    }

    protected function sanitizeUpdateScript(array $updateScript): array
    {
        if (!isset($updateScript['script']) && count($updateScript) > 1) {
            $sanitizedUpdateScript = [
                'script' => [
                    'lang'   => $updateScript['lang'] ?? null,
                    'source' => $updateScript['source'] ?? [],
                    'params' => $updateScript['params'] ?? [],
                ],
            ];
        } else {
            $sanitizedUpdateScript = $updateScript;
        }

        return $sanitizedUpdateScript;
    }

    protected function prepareDocument(array|object $entity): array
    {
        $class = $this->entityClass;
        $serializer = $this->config->getEntitySerializer();

        return match (true) {
            is_object($entity) => match (true) {
                is_string($class) && $entity instanceof $class   => $entity->toElastic(),
                $serializer instanceof EntitySerializerInterface => $serializer->toElastic($entity),
                default                                          => throw new RepositoryConfigurationException(
                    'No entity serializer configured while trying to persist object'
                ),
            },
            default            => $entity,
        };
    }

    private function createPotentialEntity(array $hit): array|object
    {
        /** @var ?PersistableEntityInterface $entityClass */
        // @phpstan-ignore varTag.nativeType
        $entityClass = $this->entityClass;

        if ((null !== $entityClass) || $this->entityFactory) {
            ['source' => $source, 'meta' => $metaData] = $this->splitSourceAndMetaData($hit);

            return $entityClass
                ? $entityClass::fromElasticDocument($source, $metaData)
                : $this->entityFactory->fromDocument($source, $metaData);
        }

        return $hit;
    }

    private function getBodyForMultipleGet(array $ids, array $sourceFields): array
    {
        $docs = [];
        foreach ($ids as $id) {
            $doc['_id'] = $id;

            if (!empty($sourceFields)) {
                $doc['_source'] = $sourceFields;
            }

            $docs[] = $doc;
        }

        return ['docs' => $docs];
    }
}
