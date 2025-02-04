<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

final class AggregationResultSet extends AbstractAggregationResultSet
{
    public static function create(array $rawResult = []): self
    {
        return new self($rawResult);
    }
}
