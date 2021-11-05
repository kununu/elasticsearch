<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

trait CountableTrait
{
    protected array $results = [];

    public function count(): int
    {
        return count($this->results);
    }
}
