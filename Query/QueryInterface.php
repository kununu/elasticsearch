<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

/**
 * Interface QueryInterface
 *
 * @package App\Services\Elasticsearch\Query
 */
interface QueryInterface
{
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

    /**
     * @return int|null
     */
    public function getLimit(): ?int;

    /**
     * @param string $field
     * @param string $direction
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public function sort(string $field, string $direction): QueryInterface;

    /**
     * @return array
     */
    public function getSort(): array;

    /**
     * @param array $selectFields
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public function select(array $selectFields): QueryInterface;
}
