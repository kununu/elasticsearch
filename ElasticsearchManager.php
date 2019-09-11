<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Exception\ManagerConfigurationException;
use Elastica\Client;
use Elastica\Query;
use Elastica\Result;
use Psr\Log\LoggerInterface;

/**
 * Class ElasticsearchManager
 *
 * @package App\Services\Elasticsearch
 */
class ElasticsearchManager implements ElasticsearchManagerInterface
{
    protected const EXCEPTION_PREFIX = 'Elasticsearch exception: ';

    /** @var Client */
    protected $client;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $index;

    /**
     * AbstractElasticsearchManager constructor.
     *
     * @param \Elastica\Client         $client
     * @param \Psr\Log\LoggerInterface $logger
     * @param string                   $index
     */
    public function __construct(Client $client, LoggerInterface $logger, string $index)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->index = $index;
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
     * @return string
     */
    protected function getIndex(): string
    {
        if (!$this->index) {
            throw new ManagerConfigurationException('no index defined');
        }

        return $this->index;
    }

    /**
     * @return string
     */
    protected function getType(): string
    {
        return '_doc';
    }

    /**
     * @inheritdoc
     */
    public function save(string $id, array $document): void
    {
        try {
            $type = $this->client->getIndex($this->getIndex())->getType($this->getType());
            $type->addDocument($type->createDocument($id, $document));
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
            $this->client->getIndex($this->getIndex())->getType($this->getType())->deleteById($id);
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteIndex(): void
    {
        try {
            $this->client->getIndex($this->getIndex())->delete();
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function findAll(): array
    {
        try {
            return array_map(
                function (Result $result) {
                    return $result->getData();
                },
                $this->client->getIndex($this->getIndex())->getType($this->getType())->search()->getResults()
            );
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function findByQuery(Query $query): array
    {
        try {
            return array_map(
                function (Result $result) {
                    return $result->getData();
                },
                $this->client->getIndex($this->getIndex())->getType($this->getType())->search($query)->getResults()
            );
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        try {
            return $this->client->getIndex($this->getIndex())->getType($this->getType())->count();
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function countByQuery(Query $query): int
    {
        try {
            return $this->client->getIndex($this->getIndex())->getType($this->getType())->count($query);
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function aggregateByQuery(Query $query): array
    {
        try {
            return $this->client->getIndex($this->getIndex())->getType($this->getType())->search(
                $query
            )->getAggregations();
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }
}
