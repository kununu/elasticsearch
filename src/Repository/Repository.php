<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
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
use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Throwable;

class Repository implements RepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const EXCEPTION_PREFIX = 'Elasticsearch exception: ';

    protected readonly RepositoryConfiguration $config;

    public function __construct(protected readonly Client $client, array $config)
    {
        $this->config = new RepositoryConfiguration($config);
    }

    public function save(string $id, array|object $entity): void
    {
        $document = $this->prepareDocument($entity);

        try {
            $this->client->index(
                array_merge($this->buildRequestBase(OperationType::WRITE), ['id' => $id, 'body' => $document])
            );

            $this->postSave($id, $document);
        } catch (Throwable $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new UpsertException($e->getMessage(), $e, $id, $document);
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
        } catch (Throwable $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new BulkException($e->getMessage(), $e, $body);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->client->delete(
                array_merge($this->buildRequestBase(OperationType::WRITE), ['id' => $id])
            );

            $this->postDelete($id);
        } catch (Missing404Exception $e) {
            throw new DocumentNotFoundException('No document found with id ' . $id, $e, $id);
        } catch (Throwable $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new DeleteException($e->getMessage(), $e, $id);
        }
    }

    public function deleteByQuery(QueryInterface $query, bool $proceedOnConflicts = false): array
    {
        return $this->executeWrite(
            function() use ($query, $proceedOnConflicts) {
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
        } catch (Throwable $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new BulkException($e->getMessage(), $e, $body);
        }
    }

    public function findByQuery(QueryInterface $query): ResultIteratorInterface
    {
        return $this->executeRead(
            fn() => $this->parseRawSearchResponse(
                $this->client->search($this->buildRawQuery($query, OperationType::READ))
            )
        );
    }

    public function findScrollableByQuery(
        QueryInterface $query,
        ?string $scrollContextKeepalive = null
    ): ResultIteratorInterface {
        return $this->executeRead(
            function() use ($query, $scrollContextKeepalive) {
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
        ?string $scrollContextKeepalive = null
    ): ResultIteratorInterface {
        return $this->executeRead(
            fn() => $this->parseRawSearchResponse(
                $this->client->scroll([
                    'body' => [
                        'scroll_id' => $scrollId,
                    ],
                    'scroll'    => $scrollContextKeepalive ?: $this->config->getScrollContextKeepalive(),
                ])
            )
        );
    }

    public function clearScrollId(string $scrollId): void
    {
        $this->execute(
            fn() => $this->client->clearScroll([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ]),
            ''
        );
    }

    public function findById(string $id, array $sourceFields = []): object|array|null
    {
        return $this->executeRead(
            function() use ($id, $sourceFields) {
                try {
                    $response = $this->client->get(
                        array_merge(
                            $this->buildRequestBase(OperationType::READ),
                            ['id' => $id],
                            empty($sourceFields) ? [] : ['_source' => $sourceFields]
                        )
                    );

                    if (!($response['found'] ?? false)) {
                        throw new Missing404Exception();
                    }
                } catch (Missing404Exception) {
                    return null;
                }

                if ($this->config->getEntityClass() || $this->config->getEntityFactory()) {
                    ['source' => $source, 'meta' => $metaData] = $this->splitSourceAndMetaData($response);

                    return $this->config->getEntityClass()
                        ? $this->config->getEntityClass()::fromElasticDocument($source, $metaData)
                        : $this->config->getEntityFactory()->fromDocument($source, $metaData);
                }

                return $response;
            }
        );
    }

    public function findByIds(array $ids, array $sourceFields = []): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->executeRead(
            function() use ($ids, $sourceFields) {
                $requestBody = array_merge(
                    $this->buildRequestBase(OperationType::READ),
                    ['body' => $this->getMgetBody($ids, $sourceFields)],
                );

                try {
                    $docs = $this->client->mget($requestBody);
                } catch (Throwable $exception) {
                    $this->logger->critical('Elasticsearch request error', ['request' => json_encode($requestBody)]);

                    throw $exception;
                }

                $docs = array_filter($docs['docs'] ?? [], fn($v) => $v['found'] ?? false);

                if ($this->config->getEntityClass() || $this->config->getEntityFactory()) {
                    $response = [];
                    foreach ($docs as $doc) {
                        ['source' => $source, 'meta' => $metaData] = $this->splitSourceAndMetaData($doc);

                        $response[] = $this->config->getEntityClass()
                            ? $this->config->getEntityClass()::fromElasticDocument($source, $metaData)
                            : $this->config->getEntityFactory()->fromDocument($source, $metaData);
                    }

                    return $response;
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
            fn() => $this->client->count($this->buildRawQuery($query, OperationType::READ))['count']
        );
    }

    public function aggregateByQuery(QueryInterface $query): AggregationResultSetInterface
    {
        return $this->executeRead(
            function() use ($query) {
                $result = $this->client->search(
                    $this->buildRawQuery($query, OperationType::READ)
                );

                return AggregationResultSet::create($result['aggregations'] ?? [])
                    ->setDocuments($this->parseRawSearchResponse($result));
            }
        );
    }

    /** @return Generator <CompositeResult> */
    public function aggregateCompositeByQuery(CompositeAggregationQueryInterface $query): Generator
    {
        $afterKey = null;

        do {
            $result = $this->aggregateByQuery(
                $query->withAfterKey($afterKey)->getQuery()
            )->getResultByName($query->getName());

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
            function() use ($query, $updateScript) {
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
        } catch (Throwable $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new UpsertException($e->getMessage(), $e, $id, $document);
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
        } catch (Throwable $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new UpdateException($e->getMessage(), $e, $id, $document);
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
        } catch (Throwable $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw match ($operationType) {
                OperationType::READ  => new ReadOperationException($e->getMessage(), $e),
                OperationType::WRITE => new WriteOperationException($e->getMessage(), $e),
                default              => new RepositoryException($e->getMessage(), $e),
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
        $results = $hits = $rawResult['hits']['hits'] ?? [];

        if ($this->config->getEntityClass()) {
            $results = array_map(
                function(array $hit) {
                    ['source' => $source, 'meta' => $metaData] = $this->splitSourceAndMetaData($hit);

                    return $this->config->getEntityClass()::fromElasticDocument($source, $metaData);
                },
                $hits
            );
        } elseif ($this->config->getEntityFactory()) {
            $results = array_map(
                function(array $hit) {
                    ['source' => $source, 'meta' => $metaData] = $this->splitSourceAndMetaData($hit);

                    return $this->config->getEntityFactory()->fromDocument($source, $metaData);
                },
                $hits
            );
        }

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
        $document = [];

        if (is_array($entity)) {
            $document = $entity;
        } elseif (is_object($entity)) {
            $configuredEntityClass = $this->config->getEntityClass();
            if ($configuredEntityClass && $entity instanceof $configuredEntityClass) {
                $document = $entity->toElastic();
            } elseif ($this->config->getEntitySerializer()) {
                $document = $this->config->getEntitySerializer()->toElastic($entity);
            } else {
                throw new RepositoryConfigurationException('No entity serializer configured while trying to persist object');
            }
        }

        return $document;
    }

    private function getMgetBody(array $ids, array $sourceFields): array
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
