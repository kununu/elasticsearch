<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Bool;

/**
 * Class MustNot
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Bool
 */
class MustNot extends AbstractBoolQuery
{
    public const OPERATOR = 'must_not';
}
