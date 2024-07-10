<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Generator;
use Kununu\Elasticsearch\Query\Aggregation\Sources;
use Kununu\Elasticsearch\Query\Criteria\Filters;

interface CompositeAggregationRepositoryInterface
{
    public function lookup(Filters $filters, Sources $sources, string $aggregationName): Generator;
}