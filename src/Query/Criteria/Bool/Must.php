<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

final class Must extends AbstractBoolQuery
{
    public const string OPERATOR = 'must';
}
