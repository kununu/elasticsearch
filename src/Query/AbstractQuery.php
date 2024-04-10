<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use InvalidArgumentException;

abstract class AbstractQuery implements QueryInterface
{
    protected array|null $select = null;
    protected int|null $limit = null;
    protected int|null $offset = null;
    protected array $sort = [];

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

    public function select(array $selectFields): QueryInterface
    {
        $this->select = $selectFields;

        return $this;
    }

    public function sort(string|array $field, string $order = SortOrder::ASC, array $options = []): QueryInterface
    {
        if (is_string($field)) {
            if (!in_array($order, SortOrder::all(), true)) {
                throw new InvalidArgumentException('Invalid sort direction given');
            }
            $this->sort[$field] = ['order' => $order];
        } elseif (is_array($field)) {
            array_walk(
                $field,
                fn($value, $key) => $this->sort(
                    $key,
                    $value['order'] ?? SortOrder::ASC,
                    $value['options'] ?? []
                )
            );
        }

        if (count($options)) {
            $this->sort[$field] = array_merge($this->sort[$field], $options);
        }

        return $this;
    }

    public function limit(int $size): QueryInterface
    {
        $this->limit = $size;

        return $this;
    }

    public function skip(int $offset): QueryInterface
    {
        $this->offset = $offset;

        return $this;
    }

    public function getSelect(): array|bool|null
    {
        return $this->select;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getSort(): array
    {
        return $this->sort;
    }
}
