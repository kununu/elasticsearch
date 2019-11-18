<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Adapter;

use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\AggregationResultSet;
use Kununu\Elasticsearch\Result\ResultIteratorInterface;

/**
 * Interface AdapterInterface
 *
 * @package Kununu\Elasticsearch\Adapter
 */
interface AdapterInterface
{
    /**
     * @return string
     */
    public function getIndexName(): string;

    /**
     * @return string
     */
    public function getTypeName(): string;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @param bool                                       $scroll
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
     */
    public function search(QueryInterface $query, bool $scroll = false): ResultIteratorInterface;

    /**
     * @param string $scrollId
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
     */
    public function scroll(string $scrollId): ResultIteratorInterface;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return int
     */
    public function count(QueryInterface $query): int;

    /**
     * @param string $id
     */
    public function delete(string $id): void;

    /**
     * @param string $id
     * @param array  $data
     */
    public function index(string $id, array $data): void;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return \Kununu\Elasticsearch\Result\AggregationResultSet
     */
    public function aggregate(QueryInterface $query): AggregationResultSet;

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $updateScript
     *
     * @return array
     */
    public function update(QueryInterface $query, array $updateScript): array;

    /**
     * @param string $indexName
     */
    public function deleteIndex(string $indexName): void;
}
