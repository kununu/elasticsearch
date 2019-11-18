<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use Elastica\Exception\InvalidException;
use Elastica\Query\AbstractQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\QueryString;
use Elastica\Suggest;
use Elastica\Suggest\AbstractSuggest;
use InvalidArgumentException;

/**
 * Class ElasticaQuery
 *
 * @package Kununu\Elasticsearch\Query
 */
class ElasticaQuery extends \Elastica\Query implements QueryInterface
{
    /**
     * Need to override this method from \Elastica\Query to make use of late static binding.
     *
     * @inheritdoc
     *
     * @return \Kununu\Elasticsearch\Query\ElasticaQuery
     */
    public static function create($query = null): ElasticaQuery
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
     * Inherit this method to comply with type hints in \Kununu\Elasticsearch\Query\QueryInterface
     *
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
    public function sort(string $field, string $order = SortOrder::ASC): QueryInterface
    {
        if (!in_array($order, SortOrder::all(), true)) {
            throw new InvalidArgumentException('Unknown $direction given');
        }

        parent::addSort([$field => $order]);

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

    /**
     * @inheritdoc
     */
    public function select(array $selectFields): QueryInterface
    {
        parent::setSource(count($selectFields) ? array_values(array_unique($selectFields)) : false);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSelect()
    {
        return parent::getParam('_source');
    }
}
