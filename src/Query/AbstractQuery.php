<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use InvalidArgumentException;

/**
 * Class AbstractQuery
 *
 * @package Kununu\Elasticsearch\Query
 */
abstract class AbstractQuery implements QueryInterface
{
    /**
     * @var array|null
     */
    protected $select;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var array
     */
    protected $sort = [];

    /**
     * @return array
     */
    protected function buildBaseBody(): array
    {
        $body = [];
        if ($this->limit !== null) {
            $body['size'] = $this->limit;
        }

        if ($this->offset !== null) {
            $body['from'] = $this->offset;
        }

        if (!empty($this->sort)) {
            $body['sort'] = $this->sort;
        }

        if (is_array($this->select)) {
            $body['_source'] = count($this->select) ? array_values(array_unique($this->select)) : false;
        }

        return $body;
    }

    /**
     * @param array $selectFields
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function select(array $selectFields): QueryInterface
    {
        $this->select = $selectFields;

        return $this;
    }

    /**
     * @param string|array $sort
     * @param string       $order
     * @param array        $options
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function sort($sort, $order = SortOrder::ASC, array $options = []): QueryInterface
    {
        if (is_string($sort)) {
            if (!in_array($order, SortOrder::all(), true)) {
                throw new InvalidArgumentException('Invalid sort direction given');
            }
            $this->sort[$sort] = ['order' => $order];
        } elseif (is_array($sort)) {
            array_walk(
                $sort,
                function ($value, $key) {
                    $this->sort(
                        $key,
                        $value['order'] ?? SortOrder::ASC,
                        $value['options'] ?? []
                    );
                }
            );
        }

        if (count($options)) {
            $this->sort[$sort] = array_merge($this->sort[$sort], $options);
        }

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function limit(int $limit): QueryInterface
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return \Kununu\Elasticsearch\Query\QueryInterface
     */
    public function skip(int $offset): QueryInterface
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return array|bool|null
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function getSort(): array
    {
        return $this->sort;
    }
}
