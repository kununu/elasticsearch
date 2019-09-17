<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Exception\ManagerConfigurationException;
use Elasticsearch\Client;
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
    protected $elasticsearchClient;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $index;

    /**
     * AbstractElasticsearchManager constructor.
     *
     * @param \Elasticsearch\Client    $elasticsearchClient
     * @param \Psr\Log\LoggerInterface $logger
     * @param string                   $index
     */
    public function __construct(Client $elasticsearchClient, LoggerInterface $logger, string $index)
    {
        $this->elasticsearchClient = $elasticsearchClient;
        $this->logger = $logger;
        $this->index = $index;

        if (empty($this->index)) {
            throw new ManagerConfigurationException('no index defined');
        }
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
            $this->elasticsearchClient->index(
                [
                    'index' => $this->getIndex(),
                    'type' => $this->getType(),
                    'id' => $id,
                    'body' => $document,
                ]
            );
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
            $this->elasticsearchClient->delete(
                [
                    'index' => $this->getIndex(),
                    'type' => $this->getType(),
                    'id' => $id,
                ]
            );
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
            $this->elasticsearchClient->indices()->delete(
                [
                    'index' => $this->getIndex(),
                ]
            );
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
            $result = $this->elasticsearchClient->search(
                [
                    'index' => $this->getIndex(),
                ]
            );

            return $result['hits']['hits'];
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function updateByQuery(array $query): array
    {
        try {
            return $this->elasticsearchClient->updateByQuery(
                [
                    'index' => $this->getIndex(),
                    'body' => $query,
                ]
            );
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }
}
