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
use Kununu\Elasticsearch\Util\UtilitiesTrait;

final class CompositeAggregationQueryBuilder implements CompositeAggregationQueryInterface
{
    use UtilitiesTrait;

    private ?array $afterKey = null;
    private Filters $filters;
    private ?string $name = null;
    private ?Sources $sources = null;

    private function __construct()
    {
        $this->filters = new Filters();
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

    public function getName(): string
    {
        if (null === $this->name) {
            throw new MissingAggregationAttributesException();
        }

        return $this->name;
    }

    public function getQuery(int $compositeSize = 100): QueryInterface
    {
        return RawQuery::create(
            self::filterNullAndEmptyValues([
                'query' => [
                    'bool' => [
                        'must' => $this->filters->map(fn(Filter $filter) => $filter->toArray()),
                    ],
                ],
                'aggs' => [
                    $this->getName() => [
                        'composite' => [
                            'size'    => $compositeSize,
                            'sources' => $this->sources?->map(
                                fn(SourceProperty $sourceProperty) => [
                                    $sourceProperty->source => [
                                        'terms' => [
                                            'field'          => $sourceProperty->property,
                                            'missing_bucket' => $sourceProperty->missingBucket,
                                        ],
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
