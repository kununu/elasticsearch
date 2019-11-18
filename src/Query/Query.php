<?php

namespace Kununu\Elasticsearch\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\Bool\Must;
use Kununu\Elasticsearch\Query\Criteria\Bool\Should;
use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;
use Kununu\Elasticsearch\Query\Criteria\FilterInterface;
use Kununu\Elasticsearch\Query\Criteria\SearchInterface;

class Query extends AbstractQuery
{
    protected const MINIMUM_SHOULD_MATCH = 1; // relevant when $searchOperator === 'should'

    /**
     * @var \Kununu\Elasticsearch\Query\Criteria\SearchInterface[]
     */
    protected $searches = [];

    /**
     * @var \Kununu\Elasticsearch\Query\Criteria\FilterInterface[]
     */
    protected $filters = [];

    /**
     * @var \Kununu\Elasticsearch\Query\AggregationInterface[]
     */
    protected $aggregations = [];

    /**
     * @var float
     */
    protected $minScore;

    /**
     * @var string
     */
    protected $searchOperator = Should::OPERATOR;

    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\CriteriaInterface[] ...$children
     */
    public function __construct(...$children)
    {
        $children = array_filter($children);
        foreach ($children as $ii => $child) {
            $this->addChild($child, $ii);
        }
    }

    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\CriteriaInterface[] ...$children
     *
     * @return \Kununu\Elasticsearch\Query\Query
     */
    public static function create(...$children): Query
    {
        return new static(...$children);
    }

    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\CriteriaInterface|\Kununu\Elasticsearch\Query\AggregationInterface $child
     *
     * @return \Kununu\Elasticsearch\Query\Query
     */
    public function add($child): Query
    {
        $this->addChild($child);

        return $this;
    }

    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\SearchInterface|\Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface $search
     *
     * @return \Kununu\Elasticsearch\Query\Query
     */
    public function search(CriteriaInterface $search): Query
    {
        if (!($search instanceof SearchInterface) && !($search instanceof BoolQueryInterface)) {
            throw new InvalidArgumentException(
                'Argument $search must implement \Kununu\Elasticsearch\Query\Criteria\SearchInterface or \Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface'
            );
        }

        $this->searches[] = $search;

        return $this;
    }

    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\FilterInterface $filter
     *
     * @return \Kununu\Elasticsearch\Query\Query
     */
    public function where(FilterInterface $filter): Query
    {
        return $this->add($filter);
    }

    /**
     * @param \Kununu\Elasticsearch\Query\AggregationInterface $aggregation
     *
     * @return \Kununu\Elasticsearch\Query\Query
     */
    public function aggregate(AggregationInterface $aggregation): Query
    {
        $this->addChild($aggregation);

        return $this;
    }

    /**
     * @param mixed $child
     * @param int   $argumentIndex
     */
    protected function addChild($child, int $argumentIndex = 0): void
    {
        switch (true) {
            case $child instanceof FilterInterface:
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

    /**
     * @return array
     */
    public function toArray(): array
    {
        $body = $this->buildBaseBody();

        if (!empty($this->searches)) {
            $preparedSearches = array_map(
                function (CriteriaInterface $search): array {
                    return $search->toArray();
                },
                $this->searches
            );

            switch ($this->searchOperator) {
                case Must::OPERATOR:
                    $body['query'] = ['bool' => ['must' => $preparedSearches]];
                    break;
                case Should::OPERATOR:
                default:
                    $body['query'] = [
                        'bool' => [
                            'should' => $preparedSearches,
                            'minimum_should_match' => static::MINIMUM_SHOULD_MATCH,
                        ],
                    ];
                    break;
            }
        }

        if (!empty($this->filters)) {
            $body['query']['bool']['filter'] = Must::create(...$this->filters)->toArray();
        }

        if ($this->minScore !== null) {
            $body['min_score'] = $this->minScore;
        }

        if (!empty($this->aggregations)) {
            $body['aggs'] = [];
            foreach ($this->aggregations as $aggregation) {
                $body['aggs'] = array_merge($body['aggs'], $aggregation->toArray());
            }
        }

        return $body;
    }

    /**
     * @return float|null
     */
    public function getMinScore(): ?float
    {
        return $this->minScore;
    }

    /**
     * @param float $minScore
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function setMinScore(float $minScore): QueryInterface
    {
        $this->minScore = $minScore;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchOperator(): string
    {
        return $this->searchOperator;
    }

    /**
     * @param string $logicalOperator
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function setSearchOperator(string $logicalOperator): QueryInterface
    {
        if (!\in_array($logicalOperator, [Must::OPERATOR, Should::OPERATOR], true)) {
            throw new InvalidArgumentException("The value '$logicalOperator' is not valid.");
        }

        $this->searchOperator = $logicalOperator;

        return $this;
    }
}
