<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

/**
 * Trait CountableTrait
 *
 * @package Kununu\Elasticsearch\Result
 */
trait CountableTrait
{
    /**
     * @var array
     */
    protected $results;

    /**
     * @return int
     */
    public function count()
    {
        return count($this->results);
    }
}
