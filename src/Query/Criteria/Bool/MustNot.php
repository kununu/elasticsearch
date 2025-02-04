<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

final class MustNot extends AbstractBoolQuery
{
    use BoolQueryCreateTrait;

    public const string OPERATOR = 'must_not';
}
