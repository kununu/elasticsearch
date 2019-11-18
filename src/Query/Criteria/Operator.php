<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

use App\Services\Elasticsearch\Query\Criteria\Filter\Exists;
use App\Services\Elasticsearch\Query\Criteria\Filter\GeoDistance;
use App\Services\Elasticsearch\Query\Criteria\Filter\GeoShape;
use App\Services\Elasticsearch\Query\Criteria\Filter\Prefix;
use App\Services\Elasticsearch\Query\Criteria\Filter\Regexp;
use App\Services\Elasticsearch\Query\Criteria\Filter\Term;
use App\Services\Elasticsearch\Query\Criteria\Filter\Terms;
use App\Services\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class Operator
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
final class Operator
{
    use ConstantContainerTrait;

    public const EXISTS = Exists::KEYWORD;
    public const PREFIX = Prefix::KEYWORD;
    public const REGEXP = Regexp::KEYWORD;
    public const TERM = Term::KEYWORD;
    public const TERMS = Terms::KEYWORD;

    public const LESS_THAN = 'lt';
    public const GREATER_THAN = 'gt';
    public const LESS_THAN_EQUALS = 'lte';
    public const GREATER_THAN_EQUALS = 'gte';
    public const BETWEEN = 'between';

    public const GEO_DISTANCE = GeoDistance::KEYWORD;
    public const GEO_SHAPE = GeoShape::KEYWORD;
}
