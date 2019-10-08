<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

use App\Services\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class Operator
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
final class Operator
{
    use ConstantContainerTrait;

    public const EXISTS = 'exists';
    public const PREFIX = 'prefix';
    public const REGEXP = 'regexp';
    public const TERM = 'term';
    public const TERMS = 'terms';

    public const LESS_THAN = 'lt';
    public const GREATER_THAN = 'gt';
    public const LESS_THAN_EQUALS = 'lte';
    public const GREATER_THAN_EQUALS = 'gte';
    public const BETWEEN = 'between';

    public const GEO_DISTANCE = 'geo_distance';
    public const GEO_SHAPE = 'geo_shape';
}
