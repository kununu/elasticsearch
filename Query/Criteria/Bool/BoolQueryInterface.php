<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Bool;

use App\Services\Elasticsearch\Query\Criteria\FilterInterface;

/**
 * Interface BoolQueryInterface
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Bool
 */
interface BoolQueryInterface extends FilterInterface
{
    /**
     * @param \App\Services\Elasticsearch\Query\Criteria\FilterInterface[] $children
     */
    public function __construct(...$children);

    /**
     * @param \App\Services\Elasticsearch\Query\Criteria\FilterInterface $child
     *
     * @return \App\Services\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public function add(FilterInterface $child): BoolQueryInterface;
}
