<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Result\AggregationResultSet;
use App\Services\Elasticsearch\Result\ResultIteratorInterface;

/**
 * Interface AdapterInterface
 *
 * @package App\Services\Elasticsearch\Adapter
 */
interface AdapterInterface
{
    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @param bool                                             $scroll
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function search(QueryInterface $query, bool $scroll = false): ResultIteratorInterface;

    /**
     * @param string $scrollId
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function scroll(string $scrollId): ResultIteratorInterface;

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
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
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return \App\Services\Elasticsearch\Result\AggregationResultSet
     */
    public function aggregate(QueryInterface $query): AggregationResultSet;

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $updateScript
     *
     * @return array
     */
    public function update(QueryInterface $query, array $updateScript): array;

    /**
     * @param string $indexName
     */
    public function deleteIndex(string $indexName): void;
}
