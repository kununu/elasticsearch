<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

/**
 * Trait IterableTrait
 *
 * @package Kununu\Elasticsearch\Result
 */
trait IterableTrait
{
    /**
     * @var array
     */
    protected $results;

    /**
     * @var int
     */
    protected $position = 0;

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
}
