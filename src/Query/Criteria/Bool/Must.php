<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Bool;

/**
 * Class Must
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Bool
 */
class Must extends AbstractBoolQuery
{
    public const OPERATOR = 'must';
}
