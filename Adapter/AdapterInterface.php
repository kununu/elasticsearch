<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Result\ResultIteratorInterface;

interface AdapterInterface
{
    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function search(QueryInterface $query): ResultIteratorInterface;

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
     * @return array
     */
    public function aggregate(QueryInterface $query): array;

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $updateScript
     *
     * @return array
     */
    public function update(QueryInterface $query, array $updateScript): array;

    public function deleteIndex(): void;
}
