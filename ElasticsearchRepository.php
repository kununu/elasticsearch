<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Exception\RepositoryConfigurationException;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class ElasticsearchRepository implements ElasticsearchRepositoryInterface
{
    protected const EXCEPTION_PREFIX = 'Elasticsearch exception: ';
    public const SCROLL_CONTEXT_KEEPALIVE = '1m'; // 1 minute (see https://www.elastic.co/guide/en/elasticsearch/reference/6.5/search-request-scroll.html#scroll-search-context)

    /** @var Client */
    protected $elasticsearchClient;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $index;

    /**
     * ElasticsearchRepository constructor.
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
            throw new RepositoryConfigurationException('no index defined');
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
    public function findAll(int $size = 100): array
    {
        try {
            $result = $this->elasticsearchClient->search(
                [
                    'index' => $this->getIndex(),
                    'scroll' => self::SCROLL_CONTEXT_KEEPALIVE,
                    'size' => $size,
                ]
            );

            return $this->formatSearchResponse($result);
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function findByScrollId(string $scrollId): array
    {
        try {
            $result = $this->elasticsearchClient->scroll(
                [
                    'scroll_id' => $scrollId,
                    'scroll' => self::SCROLL_CONTEXT_KEEPALIVE,
                ]
            );

            return $this->formatSearchResponse($result);
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @param array $result
     *
     * @return array|null
     */
    protected function formatSearchResponse(array $result): array
    {
        return [
            'hits' => $result['hits']['hits'] ?? [],
            'scroll_id' => $result['_scroll_id'] ?? null,
            'total' => $result['total'] ?? 0,
        ];
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
