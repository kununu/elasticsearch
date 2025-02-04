<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

final class RawQuery extends AbstractBaseQuery
{
    public function __construct(protected readonly array $body = [])
    {
    }

    public static function create(array $rawQuery = []): self
    {
        return new self($rawQuery);
    }

    public function toArray(): array
    {
        return array_merge($this->buildBaseBody(), $this->body);
    }
}
