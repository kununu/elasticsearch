<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Bool;

/**
 * Class Should
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Bool
 */
class Should extends AbstractBoolQuery
{
    public const OPERATOR = 'should';
}
