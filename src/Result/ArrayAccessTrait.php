<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

/**
 * Trait ArrayAccessTrait
 *
 * @package Kununu\Elasticsearch\Result
 */
trait ArrayAccessTrait
{
    /**
     * @var array
     */
    protected $results;

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
}
