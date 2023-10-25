<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

use ArrayAccess;
use Countable;
use Iterator;

class ResultIterator implements Iterator, ArrayAccess, Countable, ResultIteratorInterface
{
    use ArrayAccessTrait;
    use CountableTrait;
    use IterableTrait;

    protected int $total = 0;
    protected ?string $scrollId = null;

    public function __construct(array $results = [])
    {
        $this->results = $results;
    }

    public static function create(array $results = []): ResultIterator
    {
        return new static($results);
    }

    public function setTotal(int $total): ResultIteratorInterface
    {
        $this->total = $total;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getCount(): int
    {
        return $this->count();
    }

    public function getScrollId(): ?string
    {
        return $this->scrollId;
    }

    public function setScrollId(?string $scrollId): ResultIteratorInterface
    {
        $this->scrollId = $scrollId;

        return $this;
    }

    public function asArray(): array
    {
        return $this->results;
    }

    public function push(array|object $result): ResultIteratorInterface
    {
        $this->results[] = $result;

        return $this;
    }

    public function first(callable $fn): ?array
    {
        foreach ($this->results as $result) {
            if ($fn($result)) {
                return $result;
            }
        }

        return null;
    }

    public function filter(callable $fn): array
    {
        return array_filter($this->results, $fn);
    }

    public function some(callable $fn): bool
    {
        foreach ($this->results as $key => $result) {
            if ($fn($result, $key)) {
                return true;
            }
        }

        return false;
    }

    public function every(callable $fn): bool
    {
        foreach ($this->results as $key => $result) {
            if (!$fn($result, $key)) {
                return false;
            }
        }

        return true;
    }

    public function each(callable $fn): void
    {
        foreach ($this->results as $key => $result) {
            $fn($result, $key);
        }
    }

    public function map(callable $fn): array
    {
        $arr = [];
        foreach ($this->results as $key => $result) {
            $arr[] = $fn($result, $key);
        }

        return $arr;
    }

    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this->results as $key => $result) {
            $carry = $fn($carry, $result, $key);
        }

        return $carry;
    }
}
