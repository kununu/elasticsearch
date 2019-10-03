<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

/**
 * Interface FilterInterface
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
interface FilterInterface
{
    /**
     * @return array
     */
    public function toArray(): array;
}
