<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Result;

/**
 * Class ResultIterator
 *
 * @package App\Services\Elasticsearch\Result
 */
class ResultIterator implements \Iterator, \ArrayAccess, ResultIteratorInterface
{
    /** @var int */
    protected $position = 0;

    /** @var array */
    protected $results = [];

    /** @var int */
    protected $total = 0;

    /** @var string */
    protected $scrollId;

    /**
     * @param array $results
     */
    public function __construct(array $results = [])
    {
        $this->results = $results;
    }

    /**
     * @param array $results
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public static function create(array $results = []): ResultIteratorInterface
    {
        return new static($results);
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->results[$this->position];
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return isset($this->results[$this->position]);
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->results[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->results[$offset] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $result)
    {
        if (is_null($offset)) {
            $this->results[] = $result;
        } else {
            $this->results[$offset] = $result;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->results[$offset]);
    }

    /**
     * @param int $total
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function setTotal(int $total): ResultIteratorInterface
    {
        $this->total = (int)$total;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return count($this->results);
    }

    /**
     * @return string|null
     */
    public function getScrollId(): ?string
    {
        return $this->scrollId;
    }

    /**
     * @param string|null $scrollId
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function setScrollId(?string $scrollId): ResultIteratorInterface
    {
        $this->scrollId = $scrollId;

        return $this;
    }

    /**
     * @return array
     */
    public function asArray(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @param array $result
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function push(array $result): ResultIteratorInterface
    {
        $this->results[] = $result;

        return $this;
    }

    /**
     * Returns the first result in this iterator for which the given callable returns a true-ish value.
     *
     * @param callable $fn (result)
     *
     * @return array|null
     */
    public function first(callable $fn): ?array
    {
        foreach ($this->results as $result) {
            if ($fn($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Returns true, if for at least one result in this iterator the given callable returns a true-ish value.
     *
     * @param callable $fn (result, key)
     *
     * @return bool
     */
    public function some(callable $fn): bool
    {
        foreach ($this->results as $key => $result) {
            if ($fn($result, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true, if for all results in this iterator the given callable returns a true-ish value.
     *
     * @param callable $fn (result, key)
     *
     * @return bool
     */
    public function every(callable $fn): bool
    {
        foreach ($this->results as $key => $result) {
            if (!$fn($result, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calls given callable on every result in this iterator.
     *
     * @param callable $fn (result, key)
     */
    public function each(callable $fn): void
    {
        foreach ($this->results as $key => $result) {
            $fn($result, $key);
        }
    }

    /**
     * Calls given callable on every result in this iterator and returns an array of the return values of the callable.
     *
     * @param callable $fn (result, key)
     *
     * @return array
     */
    public function map(callable $fn): array
    {
        $arr = [];
        foreach ($this->results as $key => $result) {
            $arr[] = $fn($result, $key);
        }

        return $arr;
    }

    /**
     * @param callable $fn (carry, result, key)
     * @param mixed    $initial
     *
     * @return mixed
     */
    public function reduce(callable $fn, $initial = null)
    {
        $carry = $initial;
        foreach ($this->results as $key => $result) {
            $carry = $fn($carry, $result, $key);
        }

        return $carry;
    }
}
