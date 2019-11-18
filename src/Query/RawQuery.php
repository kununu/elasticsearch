<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

/**
 * Class RawQuery
 *
 * @package Kununu\Elasticsearch\Query
 */
class RawQuery extends AbstractQuery
{
    /**
     * @var array
     */
    protected $body = [];

    /**
     * @param array $rawQuery
     */
    public function __construct(array $rawQuery = [])
    {
        $this->body = $rawQuery;
    }

    /**
     * @param array $rawQuery
     *
     * @return \Kununu\Elasticsearch\Query\RawQuery
     */
    public static function create(array $rawQuery = []): RawQuery
    {
        return new static($rawQuery);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_merge($this->buildBaseBody(), $this->body);
    }
}
