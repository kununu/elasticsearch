<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Repository;

use App\Services\Elasticsearch\Adapter\AdapterInterface;
use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Query\Query;
use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Result\ResultIteratorInterface;
use Psr\Log\LoggerInterface;

class ElasticsearchRepository implements ElasticsearchRepositoryInterface
{
    protected const EXCEPTION_PREFIX = 'Elasticsearch exception: ';

    /** @var \App\Services\Elasticsearch\Adapter\AdapterInterface */
    protected $client;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * AbstractElasticsearchManager constructor.
     *
     * @param \App\Services\Elasticsearch\Adapter\AdapterInterface $client
     * @param \Psr\Log\LoggerInterface                             $logger
     */
    public function __construct(AdapterInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param \Exception $e
     *
     * @throws \App\Services\Elasticsearch\Exception\ElasticsearchException
     */
    protected function logErrorAndThrowException(\Exception $e): void
    {
        $this->logger->error(self::EXCEPTION_PREFIX . $e->getMessage());

        throw new ElasticsearchException($e->getMessage());
    }

    /**
     * @inheritdoc
     */
    public function save(string $id, array $document): void
    {
        try {
            $this->client->index($id, $document);
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function aggregateByQuery(QueryInterface $query): array
    {
        try {
            return $this->client->aggregate($query);
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }
}
