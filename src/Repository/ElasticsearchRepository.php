<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Repository;

use App\Services\Elasticsearch\Adapter\AdapterFactoryInterface;
use App\Services\Elasticsearch\Exception\RepositoryException;
use App\Services\Elasticsearch\Query\Query;
use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Result\AggregationResultSet;
use App\Services\Elasticsearch\Result\ResultIteratorInterface;
use App\Services\Elasticsearch\Util\LoggerAwareTrait;
use Exception;
use Psr\Log\LoggerAwareInterface;

/**
 * Class ElasticsearchRepository
 *
 * @package App\Services\Elasticsearch\Repository
 */
class ElasticsearchRepository implements ElasticsearchRepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const EXCEPTION_PREFIX = 'Elasticsearch exception: ';

    /**
     * @var \App\Services\Elasticsearch\Adapter\AdapterInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $connectionConfig;

    /**
     * AbstractElasticsearchManager constructor.
     *
     * @param \App\Services\Elasticsearch\Adapter\AdapterFactoryInterface $adapterFactory
     * @param array                                                       $connectionConfig
     */
    public function __construct(AdapterFactoryInterface $adapterFactory, array $connectionConfig)
    {
        $this->client = $adapterFactory->build($connectionConfig['adapter_class'] ?? '', $connectionConfig);
        $this->connectionConfig = $connectionConfig;
    }

    /**
     * @param \Exception $e
     *
     * @throws \App\Services\Elasticsearch\Exception\RepositoryException
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
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
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
