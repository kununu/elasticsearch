<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

class Should extends AbstractBoolQuery
{
    public const OPERATOR = 'should';
}
