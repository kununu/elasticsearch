<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

/**
 * Interface QueryInterface
 *
 * @package Kununu\Elasticsearch\Query
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
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function skip(int $offset): QueryInterface;

    /**
     * @param int $size
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function limit(int $size): QueryInterface;

    /**
     * @return int|null
     */
    public function getOffset(): ?int;

    /**
     * @return array|bool|null
     */
    public function getSelect();

    /**
     * @return int|null
     */
    public function getLimit(): ?int;

    /**
     * @param string $field
     * @param string $order
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function sort(string $field, string $order = SortOrder::ASC): QueryInterface;

    /**
     * @return array
     */
    public function getSort(): array;

    /**
     * @param array $selectFields
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function select(array $selectFields): QueryInterface;
}
