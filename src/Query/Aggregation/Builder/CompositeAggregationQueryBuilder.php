<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation\Builder;

use Kununu\Elasticsearch\Exception\MissingAggregationAttributesException;
use Kununu\Elasticsearch\Query\Aggregation\SourceProperty;
use Kununu\Elasticsearch\Query\Aggregation\Sources;
use Kununu\Elasticsearch\Query\CompositeAggregationQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\Filters;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Query\RawQuery;
use Kununu\Elasticsearch\Util\ArrayUtilities;

class CompositeAggregationQueryBuilder implements CompositeAggregationQueryInterface
{
    private ?array $afterKey;
    private Filters $filters;
    private ?string $name;
    private ?Sources $sources;

    private function __construct()
    {
        $this->afterKey = null;
        $this->filters = new Filters();
        $this->name = null;
        $this->sources = null;
    }

    public static function create(): self
    {
        return new self();
    }

    public function withAfterKey(?array $afterKey): self
    {
        $this->afterKey = $afterKey;

        return $this;
    }

    public function withFilters(Filters $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withSources(Sources $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    public function name(): string
    {
        if (null === $this->name) {
            throw new MissingAggregationAttributesException('Aggregation name is missing');
        }

        return $this->name;
    }

    public function getQuery(int $compositeSize = 100): QueryInterface
    {
        return RawQuery::create(
            ArrayUtilities::filterNullAndEmptyValues([
                'query' => [
                    'bool' => [
                        'must' => $this->filters->map(fn(Filter $filter) => $filter->toArray()),
                    ],
                ],
                'aggs' => [
                    $this->name() => [
                        'composite' => [
                            'size' => $compositeSize,
                            'sources' => $this->sources?->map(
                                fn(SourceProperty $sourceProperty) => [
                                    $sourceProperty->source => [
                                        'terms' => [
                                            'field' => $sourceProperty->property,
                                            'missing_bucket' => $sourceProperty->missingBucket,
                                        ]
                                    ],
                                ]
                            ) ?? [],
                            'after' => $this->afterKey,
                        ],
                    ],
                ],
            ], true)
        );
    }

    public function toArray(): array
    {
        return $this->getQuery()->toArray();
    }
}
