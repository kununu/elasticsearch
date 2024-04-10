<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

class Must extends AbstractBoolQuery
{
    public const OPERATOR = 'must';
}
