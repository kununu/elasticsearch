<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;

final class Query extends AbstractQuery
{
    public static function create(CriteriaInterface|AggregationInterface ...$children): self
    {
        return new self(...$children);
    }

    public static function createNested(string $path, CriteriaInterface|AggregationInterface ...$children): self
    {
        return (new self(...$children))->nestAt($path);
    }
}
