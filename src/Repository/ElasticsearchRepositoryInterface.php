<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\AggregationResultSetInterface;
use Kununu\Elasticsearch\Result\ResultIteratorInterface;

/**
 * Interface ElasticsearchRepositoryInterface
 *
 * @package Kununu\Elasticsearch\Repository
 */
interface ElasticsearchRepositoryInterface
{
    /**
     * @param string $id
     * @param array  $document
     */
    public function save(string $id, array $document): void;

    /**
     * @param string $id
     */
    public function delete(string $id): void;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
     */
    public function findByQuery(QueryInterface $query): ResultIteratorInterface;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
     */
    public function findScrollableByQuery(QueryInterface $query): ResultIteratorInterface;

    /**
     * @param string $scrollId
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
     */
    public function findByScrollId(string $scrollId): ResultIteratorInterface;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return int
     */
    public function countByQuery(QueryInterface $query): int;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return \Kununu\Elasticsearch\Result\AggregationResultSetInterface
     */
    public function aggregateByQuery(QueryInterface $query): AggregationResultSetInterface;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $updateScript
     *
     * @return array
     */
    public function updateByQuery(QueryInterface $query, array $updateScript): array;
}
