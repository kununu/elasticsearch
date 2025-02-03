<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Exception\InvalidSearchArgumentException;
use Kununu\Elasticsearch\Exception\UnknownChildArgumentTypeException;
use Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\Bool\Must;
use Kununu\Elasticsearch\Query\Criteria\Bool\Should;
use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;
use Kununu\Elasticsearch\Query\Criteria\FilterInterface;
use Kununu\Elasticsearch\Query\Criteria\NestableQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\SearchInterface;
use Kununu\Elasticsearch\Util\OptionableTrait;

abstract class AbstractQuery extends AbstractBaseQuery implements NestableQueryInterface
{
    use OptionableTrait;

    public const string OPTION_MIN_SCORE = 'min_score';

    protected const int MINIMUM_SHOULD_MATCH = 1; // relevant when $searchOperator === 'should'

    protected bool $nested = false;

    /** @var array<CriteriaInterface> */
    protected array $searches = [];
    /** @var array<FilterInterface> */
    protected array $filters = [];
    /** @var array<AggregationInterface> */
    protected array $aggregations = [];

    protected string $searchOperator = Should::OPERATOR;

    public function __construct(CriteriaInterface|AggregationInterface ...$children)
    {
        foreach ($children as $i => $child) {
            $this->addChild($child, $i);
        }
    }

    public function add(mixed $child): static
    {
        $this->addChild($child);

        return $this;
    }

    public function search(CriteriaInterface $search): static
    {
        $isSearch = $search instanceof SearchInterface;
        $isBool = $search instanceof BoolQueryInterface;
        $isNestedQuery = $search instanceof NestableQueryInterface;

        if (!$isSearch && !$isBool && !$isNestedQuery) {
            throw new InvalidSearchArgumentException(
                SearchInterface::class,
                BoolQueryInterface::class,
                NestableQueryInterface::class
            );
        }

        $this->searches[] = $search;

        return $this;
    }

    public function where(FilterInterface $filter): static
    {
        return $this->add($filter);
    }

    public function aggregate(AggregationInterface $aggregation): static
    {
        $this->addChild($aggregation);

        return $this;
    }

    public function toArray(): array
    {
        $body = $this->nested ? [] : $this->buildBaseBody();

        if (!empty($this->searches)) {
            $preparedSearches = array_map(
                fn(CriteriaInterface $search): array => $search->toArray(),
                $this->searches
            );

            $body['query'] = match ($this->searchOperator) {
                Must::OPERATOR => ['bool' => ['must' => $preparedSearches]],
                default        => [
                    'bool' => [
                        'should'               => $preparedSearches,
                        'minimum_should_match' => static::MINIMUM_SHOULD_MATCH,
                    ],
                ],
            };
        }

        if (!empty($this->filters)) {
            $body['query']['bool']['filter'] = Must::create(...$this->filters)->toArray();
        }

        if (!empty($this->aggregations) && !$this->nested) {
            $body['aggs'] = [];
            foreach ($this->aggregations as $aggregation) {
                $body['aggs'] = array_merge($body['aggs'], $aggregation->toArray());
            }
        }

        $body = array_merge($body, $this->getOptions());

        return $this->nested ? ['nested' => $body] : $body;
    }

    public function setMinScore(float $minScore): static
    {
        return $this->setOption(static::OPTION_MIN_SCORE, $minScore);
    }

    public function getSearchOperator(): string
    {
        return $this->searchOperator;
    }

    public function setSearchOperator(string $logicalOperator): static
    {
        if (!in_array($logicalOperator, [Must::OPERATOR, Should::OPERATOR], true)) {
            throw new InvalidArgumentException("The value '$logicalOperator' is not valid.");
        }

        $this->searchOperator = $logicalOperator;

        return $this;
    }

    protected function getAvailableOptions(): array
    {
        return $this->nested
            ? [
                NestableQueryInterface::OPTION_PATH,
                NestableQueryInterface::OPTION_IGNORE_UNMAPPED,
                NestableQueryInterface::OPTION_SCORE_MODE,
                NestableQueryInterface::OPTION_INNER_HITS,
            ]
            : [static::OPTION_MIN_SCORE];
    }

    protected function nestAt(string $path): static
    {
        $this->nested = true;
        $this->setOption(static::OPTION_PATH, $path);

        return $this;
    }

    protected function addChild(mixed $child, int $argumentIndex = 0): void
    {
        match (true) {
            $child instanceof FilterInterface,
            $child instanceof NestableQueryInterface => $this->filters[] = $child,
            $child instanceof SearchInterface        => $this->searches[] = $child,
            $child instanceof AggregationInterface   => $this->aggregations[] = $child,
            default                                  => throw new UnknownChildArgumentTypeException($argumentIndex),
        };
    }
}
