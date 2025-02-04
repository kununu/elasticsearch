<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;

trait BoolQueryCreateTrait
{
    public static function create(CriteriaInterface ...$children): self
    {
        return new self(...$children);
    }
}
