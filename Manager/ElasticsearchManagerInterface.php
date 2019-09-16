<?php

namespace App\Services\Elasticsearch\Manager;

use App\Services\Elasticsearch\Query\QueryInterface;

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
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return array
     */
    public function findByQuery(QueryInterface $query): array;

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
}