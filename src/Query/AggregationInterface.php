<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

/**
 * Interface AggregationInterface
 *
 * @package App\Services\Elasticsearch\Query
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
