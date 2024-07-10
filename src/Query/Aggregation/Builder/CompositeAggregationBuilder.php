<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation\Builder;

use Kununu\Elasticsearch\Query\Aggregation\SourceProperty;
use Kununu\Elasticsearch\Query\Aggregation\Sources;
use Kununu\Elasticsearch\Query\AggregationInterface;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\Filters;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Query\RawQuery;
use Kununu\Utilities\Arrays\ArrayUtilities;
use Kununu\Utilities\Elasticsearch\Q;
use RuntimeException;

class CompositeAggregationBuilder implements AggregationInterface
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

    public function getName(): string
    {
        if (null === $this->name) {
            throw new RuntimeException('Aggregation name is required');
        }

        return $this->name;
    }

    public function getQuery(int $compositeSize = 100): QueryInterface
    {
        return RawQuery::create(
            ArrayUtilities::filterNullAndEmptyValues([
                Q::query() => [
                    Q::bool() => [
                        Q::must() => $this->filters->map(fn(Filter $filter) => $filter->toArray()),
                    ],
                ],
                Q::aggs() => [
                    $this->getName() => [
                        Q::composite() => [
                            Q::size() => $compositeSize,
                            Q::sources() => $this->sources?->map(
                                    fn(SourceProperty $sourceProperty) => [
                                        $sourceProperty->source => [
                                            Q::terms() => [
                                                Q::field() => $sourceProperty->property,
                                                Q::missingBucket() => $sourceProperty->missingBucket,
                                            ]
                                        ],
                                    ]
                                ) ?? [],
                            Q::after() => $this->afterKey,
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
