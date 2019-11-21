<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Elasticsearch\Client;
use Exception;
use Kununu\Elasticsearch\Exception\RepositoryException;
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
     * ElasticsearchRepository constructor.
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
    public function save(string $id, array $document): void
    {
        $this->execute(
            function () use ($id, $document) {
                $this->client->index(
                    array_merge($this->buildRequestBase(OperationType::WRITE), ['id' => $id, 'body' => $document])
                );

                $this->postSave($id, $document);
            }
        );
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
    public function delete(string $id): void
    {
        $this->execute(
            function () use ($id) {
                $this->client->delete(
                    array_merge($this->buildRequestBase(OperationType::WRITE), ['id' => $id])
                );

                $this->postDelete($id);
            }
        );
    }

    protected function postDelete(string $id): void
    {
        // ready to be overwritten :)
    }

    /**
     * @inheritdoc
     */
    public function findByQuery(QueryInterface $query): ResultIteratorInterface
    {
        return $this->execute(
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
        return $this->execute(
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
        return $this->execute(
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
    public function count(): int
    {
        return $this->countByQuery(Query::create());
    }

    /**
     * @inheritdoc
     */
    public function countByQuery(QueryInterface $query): int
    {
        return $this->execute(
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
        return $this->execute(
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
        return $this->execute(
            function () use ($query, $updateScript) {
                $rawQuery = $this->buildRawQuery($query, OperationType::WRITE);
                $rawQuery['body']['script'] = $this->sanitizeUpdateScript($updateScript)['script'];

                return $this->client->updateByQuery($rawQuery);
            }
        );
    }

    protected function execute(callable $operation)
    {
        try {
            return $operation();
        } catch (Exception $e) {
            $this->getLogger()->error(self::EXCEPTION_PREFIX . $e->getMessage());

            throw new RepositoryException($e->getMessage(), $e);
        }
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
     * @param string $operationType
     *
     * @return array
     */
    protected function buildRequestBase(string $operationType): array
    {
        return [
            'index' => $this->config->getIndex($operationType),
            'type' => $this->config->getType(),
        ];
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
        return ResultIterator::create($rawResult['hits']['hits'] ?? [])
            ->setTotal($rawResult['hits']['total'] ?? 0)
            ->setScrollId($rawResult['_scroll_id'] ?? null);
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
}