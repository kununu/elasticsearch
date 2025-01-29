<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

/** @phpstan-consistent-constructor */
class RawQuery extends AbstractQuery
{
    public function __construct(protected readonly array $body = [])
    {
    }

    public static function create(array $rawQuery = []): RawQuery
    {
        return new static($rawQuery);
    }

    public function toArray(): array
    {
        return array_merge($this->buildBaseBody(), $this->body);
    }
}
