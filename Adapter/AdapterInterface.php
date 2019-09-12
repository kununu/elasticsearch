<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Query\Query;
use App\Services\Elasticsearch\Query\QueryInterface;

interface AdapterInterface
{
    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface|null $query
     *
     * @return array
     */
    public function search(?QueryInterface $query = null): array;

    /**
     * @param \App\Services\Elasticsearch\Query\Query|null $query
     *
     * @return int
     */
    public function count(?Query $query = null): int;

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
     * @param \App\Services\Elasticsearch\Query\QueryInterface|null $query
     *
     * @return array
     */
    public function aggregate(?QueryInterface $query = null): array;

    public function deleteIndex(): void;
}