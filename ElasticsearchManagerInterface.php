<?php

namespace App\Services\Elasticsearch;

use Elastica\Query;

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
     * @return array
     */
    public function findAll(): array;

    /**
     * @param \Elastica\Query $query
     *
     * @return array
     */
    public function findByQuery(Query $query): array;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @param \Elastica\Query $query
     *
     * @return int
     */
    public function countByQuery(Query $query): int;

    /**
     * @param \Elastica\Query $query
     *
     * @return array
     */
    public function aggregateByQuery(Query $query): array;
}