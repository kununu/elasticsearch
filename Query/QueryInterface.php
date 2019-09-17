<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

interface QueryInterface
{
    /**
     * @param mixed $query
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public static function create($query);

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param int $offset
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public function skip(int $offset): QueryInterface;

    /**
     * @param int $size
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public function limit(int $size): QueryInterface;

    /**
     * @return int|null
     */
    public function getOffset(): ?int;

    public function getLimit(): ?int;

    public function sort(string $field, string $direction): QueryInterface;

    public function getSort(): array;
}
