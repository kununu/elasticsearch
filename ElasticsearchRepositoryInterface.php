<?php

namespace App\Services\Elasticsearch;

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

    public function deleteIndex(): void;

    /**
     * @param int $size
     *
     * @return array
     */
    public function findAll(int $size): array;

    /**
     * @param string $scrollId
     *
     * @return array
     */
    public function findByScrollId(string $scrollId): array;

    /**
     * @param array $query
     *
     * @return array
     */
    public function updateByQuery(array $query): array;
}
