<?php

namespace App\Services\Elasticsearch\Manager;

use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Result\ResultIteratorInterface;

/**
 * Interface ElasticsearchManagerInterface
 *
 * @package App\Services\Elasticsearch
 */
interface ElasticsearchManagerInterface
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

    public function deleteIndex(): void;

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function findByQuery(QueryInterface $query): ResultIteratorInterface;

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function findScrollableByQuery(QueryInterface $query): ResultIteratorInterface;

    /**
     * @param string $scrollId
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function findByScrollId(string $scrollId): ResultIteratorInterface;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return int
     */
    public function countByQuery(QueryInterface $query): int;

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return array
     */
    public function aggregateByQuery(QueryInterface $query): array;

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $updateScript
     *
     * @return array
     */
    public function updateByQuery(QueryInterface $query, array $updateScript): array;
}
