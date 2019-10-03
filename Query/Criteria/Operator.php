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

    public const TERM = '__term';
    public const TERMS = '__terms';
    public const PREFIX = '__prefix';

    public const LESS_THAN = '__lt';
    public const GREATER_THAN = '__gt';
    public const LESS_THAN_EQUALS = '__lte';
    public const GREATER_THAN_EQUALS = '__gte';
    public const BETWEEN = '__gte';

    public const EXISTS = '__exists';
    public const REGEX = '__regex';
    public const GEO_DISTANCE = '__geo_distance';
    public const GEO_SHAPE = '__geo_shape';
}
