<?php

namespace App\Services\Elasticsearch;

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
}
