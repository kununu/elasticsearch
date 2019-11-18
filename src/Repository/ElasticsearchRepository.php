<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Exception;
use Kununu\Elasticsearch\Adapter\AdapterFactoryInterface;
use Kununu\Elasticsearch\Exception\RepositoryException;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\AggregationResultSet;
use Kununu\Elasticsearch\Result\ResultIteratorInterface;
use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * Class ElasticsearchRepository
 *
 * @package Kununu\Elasticsearch\Repository
 */
class ElasticsearchRepository implements ElasticsearchRepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const EXCEPTION_PREFIX = 'Elasticsearch exception: ';

    /**
     * @var \Kununu\Elasticsearch\Adapter\AdapterInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $connectionConfig;

    /**
     * AbstractElasticsearchManager constructor.
     *
     * @param \Kununu\Elasticsearch\Adapter\AdapterFactoryInterface $adapterFactory
     * @param array                                                 $connectionConfig
     */
    public function __construct(AdapterFactoryInterface $adapterFactory, array $connectionConfig)
    {
        $this->client = $adapterFactory->build($connectionConfig['adapter_class'] ?? '', $connectionConfig);
        $this->connectionConfig = $connectionConfig;
    }

    /**
     * @param \Exception $e
     *
     * @throws \Kununu\Elasticsearch\Exception\RepositoryException
     */
    protected function logErrorAndThrowException(Exception $e): void
    {
        $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

        throw new RepositoryException($e->getMessage(), $e);
    }

    /**
     * @inheritdoc
     */
    public function save(string $id, array $document): void
    {
        try {
            $this->client->index($id, $document);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(string $id): void
    {
        try {
            $this->client->delete($id);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteIndex(string $indexName): void
    {
        try {
            $this->client->deleteIndex($indexName);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function findByQuery(QueryInterface $query): ResultIteratorInterface
    {
        try {
            return $this->client->search($query);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
     */
    public function findScrollableByQuery(QueryInterface $query): ResultIteratorInterface
    {
        try {
            return $this->client->search($query, true);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function findByScrollId(string $scrollId): ResultIteratorInterface
    {
        try {
            return $this->client->scroll($scrollId);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
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
        try {
            return $this->client->count($query);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function aggregateByQuery(QueryInterface $query): AggregationResultSet
    {
        try {
            return $this->client->aggregate($query);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function updateByQuery(QueryInterface $query, array $updateScript): array
    {
        try {
            return $this->client->update($query, $updateScript);
        } catch (Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }
}
