<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Generator;
use Kununu\Elasticsearch\Query\Aggregation\Builder\CompositeAggregationBuilder;
use Kununu\Elasticsearch\Query\Aggregation\Sources;
use Kununu\Elasticsearch\Query\Criteria\Filters;
use Kununu\Elasticsearch\Result\CompositeResult;
use Kununu\Utilities\Elasticsearch\Q;

final class CompositeAggregationRepository extends Repository implements CompositeAggregationRepositoryInterface
{
    public function lookup(Filters $filters, Sources $sources, string $aggregationName): Generator
    {
        $elasticsearchQuery = CompositeAggregationBuilder::create()
            ->withName($aggregationName)
            ->withFilters($filters)
            ->withSources($sources);

        do {
            $result = $this->aggregateByQuery(
                $elasticsearchQuery->getQuery()->limit(0)
            )->getResultByName($aggregationName);

            foreach ($result?->getFields()[Q::buckets()] ?? [] as $bucket) {
                if (!empty($bucket[Q::key()]) && !empty($bucket[Q::docCount()])) {
                    yield new CompositeResult(
                        $bucket[Q::key()],
                        $bucket[Q::docCount()],
                        $aggregationName
                    );
                }
            }

            $elasticsearchQuery->withAfterKey($afterKey = ($result?->get(Q::afterKey()) ?? null));
        } while (null !== $afterKey);
    }
}
