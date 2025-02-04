<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

final class Should extends AbstractBoolQuery
{
    use BoolQueryCreateTrait;

    public const string OPERATOR = 'should';
}
