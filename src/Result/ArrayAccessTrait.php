<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

trait ArrayAccessTrait
{
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->results[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->results[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $result): void
    {
        if (null === $offset) {
            $this->results[] = $result;
        } else {
            $this->results[$offset] = $result;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->results[$offset]);
    }
}
