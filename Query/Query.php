<?php

namespace App\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Query\Criteria\Bool\Must;
use App\Services\Elasticsearch\Query\Criteria\Bool\Should;
use App\Services\Elasticsearch\Query\Criteria\FilterInterface;
use App\Services\Elasticsearch\Query\Criteria\SearchInterface;

class Query extends AbstractQuery
{
    /** @var array */
    protected $searches = [];

    /** @var array */
    protected $filters = [];

    /** @var float */
    protected $minScore;

    /** @var string */
    protected $searchOperator = Should::OPERATOR;

    /**
     * @param mixed ...$children
     */
    public function __construct(...$children)
    {
        $children = array_filter($children);
        foreach ($children as $ii => $child) {
            $this->addChild($child, $ii);
        }
    }

    /**
     * @param mixed ...$children
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public static function create(...$children): QueryInterface
    {
        return new static(...$children);
    }

    /**
     * @param mixed $child
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public function add($child): QueryInterface
    {
        $this->addChild($child);

        return $this;
    }

    /**
     * @param \App\Services\Elasticsearch\Query\Criteria\SearchInterface $search
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public function search(SearchInterface $search): QueryInterface
    {
        return $this->add($search);
    }

    /**
     * @param \App\Services\Elasticsearch\Query\Criteria\FilterInterface $filter
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public function filter(FilterInterface $filter): QueryInterface
    {
        return $this->add($filter);
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
            default:
                throw new \InvalidArgumentException('Argument #' . $argumentIndex . ' is of unknown type');
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
                function (SearchInterface $search): array {
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
                            'minimum_should_match' => 1,
                        ],
                    ];
                    break;
            }
        }

        if (!empty($this->filters)) {
            if ($this->minScore !== null) {
                $body['min_score'] = $this->minScore;
            }

            $body['query']['bool']['filter'] = Must::create(...$this->filters)->toArray();
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
     * @return \App\Services\Elasticsearch\Query\QueryInterface
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
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public function setSearchOperator(string $logicalOperator): QueryInterface
    {
        if (!\in_array($logicalOperator, [Must::OPERATOR, Should::OPERATOR], true)) {
            throw new \InvalidArgumentException("The value $logicalOperator is not valid.");
        }

        $this->searchOperator = $logicalOperator;

        return $this;
    }
}
