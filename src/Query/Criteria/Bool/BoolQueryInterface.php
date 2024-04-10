<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;
use Kununu\Elasticsearch\Query\Criteria\FilterInterface;

interface BoolQueryInterface extends FilterInterface
{
    public function add(CriteriaInterface $child): BoolQueryInterface;
}
