<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Bool;

use App\Services\Elasticsearch\Query\Criteria\CriteriaInterface;
use App\Services\Elasticsearch\Query\Criteria\FilterInterface;

/**
 * Interface BoolQueryInterface
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Bool
 */
interface BoolQueryInterface extends FilterInterface
{
    /**
     * @param \App\Services\Elasticsearch\Query\Criteria\CriteriaInterface $child
     *
     * @return \App\Services\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public function add(CriteriaInterface $child): BoolQueryInterface;
}
