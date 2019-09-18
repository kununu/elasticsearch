<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

use Elastica\Exception\InvalidException;
use Elastica\Query\AbstractQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\QueryString;
use Elastica\Suggest;
use Elastica\Suggest\AbstractSuggest;

class Query extends \Elastica\Query implements QueryInterface
{
    /**
     * Need to override this method from \Elastica\Query to make use of late static binding.
     *
     * @inheritdoc
     *
     * @return \App\Services\Elasticsearch\Query\Query
     */
    public static function create($query = null): Query
    {
        switch (true) {
            case $query instanceof self:
                $ret = $query;
                break;
            case $query instanceof AbstractQuery:
            case is_array($query):
            case $query instanceof Suggest:
                $ret = new static($query);
                break;
            case empty($query):
                $ret = new static(new MatchAll());
                break;
            case is_string($query):
                $ret = new static(new QueryString($query));
                break;
            case $query instanceof AbstractSuggest:
                $ret = new static(new Suggest($query));
                break;
            default:
                throw new InvalidException('Unexpected argument to create a query for.');
        }

        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return parent::toArray();
    }

    /**
     * @inheritdoc
     */
    public function skip(int $offset): QueryInterface
    {
        parent::setFrom($offset);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function limit(int $size): QueryInterface
    {
        parent::setSize($size);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOffset(): ?int
    {
        try {
            return parent::getParam('from');
        } catch (InvalidException $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getLimit(): ?int
    {
        try {
            return parent::getParam('size');
        } catch (InvalidException $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function sort(string $field, string $direction): QueryInterface
    {
        if (!in_array($direction, SortDirection::all(), true)) {
            throw new \InvalidArgumentException('Unknown $direction given');
        }

        parent::addSort([$field => $direction]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSort(): array
    {
        try {
            return parent::getParam('sort');
        } catch (InvalidException $e) {
            return [];
        }
    }
}
