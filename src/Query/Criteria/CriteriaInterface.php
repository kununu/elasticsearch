<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

/**
 * Interface CriteriaInterface
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
interface CriteriaInterface
{
    /**
     * @return array
     */
    public function toArray(): array;
}
