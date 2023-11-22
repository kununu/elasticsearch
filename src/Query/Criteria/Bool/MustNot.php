<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

class MustNot extends AbstractBoolQuery
{
    public const OPERATOR = 'must_not';
}
