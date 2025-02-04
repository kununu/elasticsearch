<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

final class AggregationResult extends AbstractAggregationResult
{
    public static function create(string $name, array $rawResult): self
    {
        return new self($name, $rawResult);
    }
}
