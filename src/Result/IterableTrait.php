<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

trait IterableTrait
{
    protected array $results = [];

    protected int $position = 0;

    public function current(): mixed
    {
        return $this->results[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): string|float|int|bool|null
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->results[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
