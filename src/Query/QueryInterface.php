<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

interface QueryInterface
{
    public function toArray(): array;

    public function skip(int $offset): QueryInterface;

    public function limit(int $size): QueryInterface;

    public function getOffset(): ?int;

    public function getSelect(): array|bool|null;

    public function getLimit(): ?int;

    public function sort(string $field, string $order = SortOrder::ASC): QueryInterface;

    public function getSort(): array;

    public function select(array $selectFields): QueryInterface;
}
