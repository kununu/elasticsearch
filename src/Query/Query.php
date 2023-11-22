<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\Bool\Must;
use Kununu\Elasticsearch\Query\Criteria\Bool\Should;
use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;
use Kununu\Elasticsearch\Query\Criteria\FilterInterface;
use Kununu\Elasticsearch\Query\Criteria\NestableQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\SearchInterface;
use Kununu\Elasticsearch\Util\OptionableTrait;

class Query extends AbstractQuery implements NestableQueryInterface
{
    use OptionableTrait;

    protected const MINIMUM_SHOULD_MATCH = 1; // relevant when $searchOperator === 'should'
    public const OPTION_MIN_SCORE = 'min_score';

    protected bool $nested = false;

    /**
     * @var \Kununu\Elasticsearch\Query\Criteria\SearchInterface[]
     */
    protected array $searches = [];

    /**
     * @var \Kununu\Elasticsearch\Query\Criteria\FilterInterface[]
     */
    protected array $filters = [];

    /**
     * @var \Kununu\Elasticsearch\Query\AggregationInterface[]
     */
    protected array $aggregations = [];

    protected string $searchOperator = Should::OPERATOR;

    public function __construct(...$children)
    {
        $children = array_filter($children);
        foreach ($children as $ii => $child) {
            $this->addChild($child, $ii);
        }
    }

    public static function create(...$children): Query
    {
        return new static(...$children);
    }

    public static function createNested(string $path, ...$children): Query
    {
        return (new static(...$children))->nestAt($path);
    }

    public function add(mixed $child): Query
    {
        $this->addChild($child);

        return $this;
    }

    public function search(CriteriaInterface $search): Query
    {
        $isSearch = $search instanceof SearchInterface;
        $isBool = $search instanceof BoolQueryInterface;
        $isNestedQuery = $search instanceof NestableQueryInterface;

        if (!$isSearch && !$isBool && !$isNestedQuery) {
            throw new InvalidArgumentException(
                'Argument $search must be one of [\Kununu\Elasticsearch\Query\Criteria\SearchInterface, \Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface, \Kununu\Elasticsearch\Query\Criteria\NestableQueryInterface]'
            );
        }

        $this->searches[] = $search;

        return $this;
    }

    public function where(FilterInterface $filter): Query
    {
        return $this->add($filter);
    }

    public function aggregate(AggregationInterface $aggregation): Query
    {
        $this->addChild($aggregation);

        return $this;
    }

    public function toArray(): array
    {
        $body = $this->nested ? [] : $this->buildBaseBody();

        if (!empty($this->searches)) {
            $preparedSearches = array_map(
                function (CriteriaInterface $search): array {
                    return $search->toArray();
                },
                $this->searches
            );

            $body['query'] = match ($this->searchOperator) {
                Must::OPERATOR => ['bool' => ['must' => $preparedSearches]],
                default => [
                    'bool' => [
                        'should' => $preparedSearches,
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

    public function setMinScore(float $minScore): Query
    {
        return $this->setOption(static::OPTION_MIN_SCORE, $minScore);
    }

    public function getSearchOperator(): string
    {
        return $this->searchOperator;
    }

    public function setSearchOperator(string $logicalOperator): Query
    {
        if (!\in_array($logicalOperator, [Must::OPERATOR, Should::OPERATOR], true)) {
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

    protected function nestAt(string $path): Query
    {
        $this->nested = true;
        $this->setOption(static::OPTION_PATH, $path);

        return $this;
    }

    protected function addChild(mixed $child, int $argumentIndex = 0): void
    {
        switch (true) {
            case $child instanceof FilterInterface:
            case $child instanceof NestableQueryInterface:
                $this->filters[] = $child;
                break;
            case $child instanceof SearchInterface:
                $this->searches[] = $child;
                break;
            case $child instanceof AggregationInterface:
                $this->aggregations[] = $child;
                break;
            default:
                throw new InvalidArgumentException('Argument #' . $argumentIndex . ' is of unknown type');
        }
    }
}
