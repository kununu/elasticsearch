<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

trait CountableTrait
{
    public function count(): int
    {
        return count($this->results);
    }
}
