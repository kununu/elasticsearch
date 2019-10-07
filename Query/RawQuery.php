<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

/**
 * Class RawQuery
 *
 * @package App\Services\Elasticsearch\Query
 */
class RawQuery extends AbstractQuery
{
    /** @var array */
    protected $body = [];

    /** @var array */
    protected $aggregations = [];

    /**
     * @param array $rawQuery
     * @param array $aggregations
     */
    public function __construct(array $rawQuery = [], array $aggregations = [])
    {
        $this->body = $rawQuery;
        $this->aggregations = $aggregations;
    }

    /**
     * @param array $rawQuery
     * @param array $aggregations
     *
     * @return \App\Services\Elasticsearch\Query\RawQuery
     */
    public static function create(array $rawQuery = [], array $aggregations = []): RawQuery
    {
        return new static($rawQuery, $aggregations);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = array_merge($this->buildBaseBody(), $this->body);

        if (!empty($this->aggregations)) {
            $result = array_merge($result, ['aggs' => $this->aggregations]);
        }

        return $result;
    }

    /**
     * @param array $query
     *
     * @return \App\Services\Elasticsearch\Query\RawQuery
     */
    public function setQuery(array $query): RawQuery
    {
        $this->body = $query;

        return $this;
    }

    /**
     * @param array $aggregations
     *
     * @return \App\Services\Elasticsearch\Query\RawQuery
     */
    public function setAggregations(array $aggregations): RawQuery
    {
        $this->aggregations = $aggregations;

        return $this;
    }
}
