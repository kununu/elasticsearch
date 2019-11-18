<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;
use Kununu\Elasticsearch\Query\Criteria\FilterInterface;

/**
 * Interface BoolQueryInterface
 *
 * @package Kununu\Elasticsearch\Query\Criteria\Bool
 */
interface BoolQueryInterface extends FilterInterface
{
    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\CriteriaInterface $child
     *
     * @return \Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public function add(CriteriaInterface $child): BoolQueryInterface;
}
