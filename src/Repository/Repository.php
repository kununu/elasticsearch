<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Exception;
use InvalidArgumentException;
use Kununu\Elasticsearch\Exception\BulkException;
use Kununu\Elasticsearch\Exception\DeleteException;
use Kununu\Elasticsearch\Exception\DocumentNotFoundException;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Exception\RepositoryException;
use Kununu\Elasticsearch\Exception\UpsertException;
use Kununu\Elasticsearch\Exception\WriteOperationException;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\AggregationResultSet;
use Kununu\Elasticsearch\Result\AggregationResultSetInterface;
use Kununu\Elasticsearch\Result\ResultIterator;
use Kununu\Elasticsearch\Result\ResultIteratorInterface;
use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * Class Repository
 *
 * @package Kununu\Elasticsearch\Repository
 */
class Repository implements RepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const EXCEPTION_PREFIX = 'Elasticsearch exception: ';

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var \Kununu\Elasticsearch\Repository\RepositoryConfiguration
     */
    protected $config;

    /**
     * Repository constructor.
     *
     * @param \Elasticsearch\Client $client
     * @param array                 $config
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = new RepositoryConfiguration($config);
    }

    /**
     * @inheritdoc
     */
    public function save(string $id, $entity): void
    {
        $document = $this->prepareDocument($entity);

        try {
            $this->client->index(
                array_merge($this->buildRequestBase(OperationType::WRITE), ['id' => $id, 'body' => $document])
            );

            $this->postSave($id, $document);
        } catch (\Exception $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new UpsertException($e->getMessage(), $e, $id, $document);
        }
    }

    /**
     * @param string $id
     * @param array  $document
     */
    protected function postSave(string $id, array $document): void
    {
        // ready to be overwritten :)
    }

    /**
     * @inheritdoc
     */
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
        } catch (\Exception $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new BulkException($e->getMessage(), $e, $body);
        }
    }

    /**
     * @param array $entities
     */
    protected function postSaveBulk(array $entities): void
    {
        // ready to be overwritten :)
    }

    /**
     * @inheritdoc
     */
    public function delete(string $id): void
    {
        try {
            $this->client->delete(
                array_merge($this->buildRequestBase(OperationType::WRITE), ['id' => $id])
            );

            $this->postDelete($id);
        } catch (Missing404Exception $e) {
            throw new DocumentNotFoundException('No document found with id ' . $id, $e, $id);
        } catch (\Exception $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new DeleteException($e->getMessage(), $e, $id);
        }
    }

    protected function postDelete(string $id): void
    {
        // ready to be overwritten :)
    }

    /**
     * @inheritdoc
     */
    public function deleteByQuery(QueryInterface $query, bool $proceedOnConflicts = false): array
    {
        return $this->executeWrite(
            function () use ($query, $proceedOnConflicts) {
                $rawQuery = $this->buildRawQuery($query, OperationType::WRITE);
                if ($proceedOnConflicts) {
                    $rawQuery['conflicts'] = 'proceed';
                }

                return $this->client->deleteByQuery($rawQuery);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function findByQuery(QueryInterface $query): ResultIteratorInterface
    {
        return $this->executeRead(
            function () use ($query) {
                return $this->parseRawSearchResponse(
                    $this->client->search($this->buildRawQuery($query, OperationType::READ))
                );
            }
        );
    }

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
     */
    public function findScrollableByQuery(QueryInterface $query): ResultIteratorInterface
    {
        return $this->executeRead(
            function () use ($query) {
                $rawQuery = $this->buildRawQuery($query, OperationType::READ);
                $rawQuery['scroll'] = $this->config->getScrollContextKeepalive();

                return $this->parseRawSearchResponse(
                    $this->client->search($rawQuery)
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function findByScrollId(string $scrollId): ResultIteratorInterface
    {
        return $this->executeRead(
            function () use ($scrollId) {
                return $this->parseRawSearchResponse(
                    $this->client->scroll(
                        [
                            'scroll_id' => $scrollId,
                            'scroll' => $this->config->getScrollContextKeepalive(),
                        ]
                    )
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function findById(string $id, array $sourceFields = [])
    {
        return $this->executeRead(
            function () use ($id, $sourceFields) {
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
                } catch (Missing404Exception $e) {
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

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return $this->countByQuery(Query::create());
    }

    /**
     * @inheritdoc
     */
    public function countByQuery(QueryInterface $query): int
    {
        return $this->executeRead(
            function () use ($query) {
                return $this->client->count($this->buildRawQuery($query, OperationType::READ))['count'];
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function aggregateByQuery(QueryInterface $query): AggregationResultSetInterface
    {
        return $this->executeRead(
            function () use ($query) {
                $result = $this->client->search(
                    $this->buildRawQuery($query, OperationType::READ)
                );

                return AggregationResultSet::create($result['aggregations'] ?? [])
                    ->setDocuments($this->parseRawSearchResponse($result));
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function updateByQuery(QueryInterface $query, array $updateScript): array
    {
        return $this->executeWrite(
            function () use ($query, $updateScript) {
                $rawQuery = $this->buildRawQuery($query, OperationType::WRITE);
                $rawQuery['body']['script'] = $this->sanitizeUpdateScript($updateScript)['script'];

                return $this->client->updateByQuery($rawQuery);
            }
        );
    }

    public function upsert(string $id, $entity): void
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
        } catch (\Exception $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new UpsertException($e->getMessage(), $e, $id, $document);
        }
    }

    /**
     * @param string $id
     * @param array  $document
     */
    protected function postUpsert(string $id, array $document): void
    {
        // ready to be overwritten :)
    }

    /**
     * @param callable $operation
     *
     * @return mixed
     */
    protected function executeRead(callable $operation)
    {
        return $this->execute($operation, OperationType::READ);
    }

    /**
     * @param callable $operation
     *
     * @return mixed
     */
    protected function executeWrite(callable $operation)
    {
        return $this->execute($operation, OperationType::WRITE);
    }

    /**
     * @param callable $operation
     * @param string   $operationType
     *
     * @return mixed
     */
    protected function execute(callable $operation, string $operationType)
    {
        try {
            return $operation();
        } catch (Exception $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            switch ($operationType) {
                case OperationType::READ:
                    throw new ReadOperationException($e->getMessage(), $e);
                case OperationType::WRITE:
                    throw new WriteOperationException($e->getMessage(), $e);
                default:
                    throw new RepositoryException($e->getMessage(), $e);
            }
        }
    }

    /**
     * @param string $operationType
     *
     * @return array
     */
    protected function buildRequestBase(string $operationType): array
    {
        $base = [
            'index' => $this->config->getIndex($operationType),
            'type' => $this->config->getType(),
        ];

        if ($operationType === OperationType::WRITE && $this->config->getForceRefreshOnWrite()) {
            $base['refresh'] = true;
        }

        return $base;
    }

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param string                                     $operationType
     *
     * @return array
     */
    protected function buildRawQuery(QueryInterface $query, string $operationType): array
    {
        return array_merge(
            $this->buildRequestBase($operationType),
            ['body' => $query->toArray()]
        );
    }

    /**
     * @param array $rawResult
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
     */
    protected function parseRawSearchResponse(array $rawResult): ResultIteratorInterface
    {
        $results = $hits = $rawResult['hits']['hits'] ?? [];

        if ($this->config->getEntityClass()) {
            $results = array_map(
                function (array $hit) {
                    ['source' => $source, 'meta' => $metaData] = $this->splitSourceAndMetaData($hit);

                    return $this->config->getEntityClass()::fromElasticDocument($source, $metaData);
                },
                $hits
            );
        } elseif ($this->config->getEntityFactory()) {
            $results = array_map(
                function (array $hit) {
                    ['source' => $source, 'meta' => $metaData] = $this->splitSourceAndMetaData($hit);

                    return $this->config->getEntityFactory()->fromDocument($source, $metaData);
                },
                $hits
            );
        }

        /**
         * This makes it compatible with Elasticsearch 6.x and 7.x.
         * Versions before 7.x total hits are passed on ['hits']['total'] while on newer versions 7.x
         * total hits are passed on ['hits']['total']['value']
         */
        $total = isset($rawResult['hits']['total']['value']) ?
            ($rawResult['hits']['total']['value'] ?? 0) :
            ($rawResult['hits']['total'] ?? 0);

        return ResultIterator::create($results)
            ->setTotal($total)
            ->setScrollId($rawResult['_scroll_id'] ?? null);
    }

    /**
     * @param array $hit
     *
     * @return array
     */
    protected function splitSourceAndMetaData(array $hit): array
    {
        $metaData = $hit;
        unset($metaData['_source']);

        return ['source' => $hit['_source'], 'meta' => $metaData];
    }

    /**
     * @param array $updateScript
     *
     * @return array
     */
    protected function sanitizeUpdateScript(array $updateScript): array
    {
        if (!isset($updateScript['script']) && count($updateScript) > 1) {
            $sanitizedUpdateScript = [
                'script' => [
                    'lang' => $updateScript['lang'] ?? null,
                    'source' => $updateScript['source'] ?? [],
                    'params' => $updateScript['params'] ?? [],
                ],
            ];
        } else {
            $sanitizedUpdateScript = $updateScript;
        }

        return $sanitizedUpdateScript;
    }

    /**
     * @param array|object $entity
     *
     * @return array
     */
    protected function prepareDocument($entity): array
    {
        if (is_array($entity)) {
            $document = $entity;
        } elseif (is_object($entity)) {
            $configuredEntityClass = $this->config->getEntityClass();
            if ($configuredEntityClass && $entity instanceof $configuredEntityClass) {
                $document = $entity->toElastic();
            } elseif ($this->config->getEntitySerializer()) {
                $document = $this->config->getEntitySerializer()->toElastic($entity);
            } else {
                throw new RepositoryConfigurationException(
                    'No entity serializer configured while trying to persist object'
                );
            }
        } else {
            throw new InvalidArgumentException('Entity must be of type array or object');
        }

        return $document;
    }
}
