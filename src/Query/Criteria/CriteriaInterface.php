<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

/**
 * Interface CriteriaInterface
 *
 * @package Kununu\Elasticsearch\Query\Criteria
 */
interface CriteriaInterface
{
    /**
     * @return array
     */
    public function toArray(): array;
}
