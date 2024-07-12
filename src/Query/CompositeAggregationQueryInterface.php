<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

interface CompositeAggregationQueryInterface
{
    public function getQuery(int $compositeSize = 100): QueryInterface;

    public function name(): string;

    public function withName(string $name): self;

    public function withAfterKey(?array $afterKey): self;

    public function toArray(): array;
}
