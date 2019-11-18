<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

/**
 * Interface AggregationInterface
 *
 * @package Kununu\Elasticsearch\Query
 */
interface AggregationInterface
{
    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @return string
     */
    public function getName(): string;
}
