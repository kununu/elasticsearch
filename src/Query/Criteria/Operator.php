<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

use Kununu\Elasticsearch\Query\Criteria\Filter\Exists;
use Kununu\Elasticsearch\Query\Criteria\Filter\GeoDistance;
use Kununu\Elasticsearch\Query\Criteria\Filter\GeoShape;
use Kununu\Elasticsearch\Query\Criteria\Filter\Prefix;
use Kununu\Elasticsearch\Query\Criteria\Filter\Regexp;
use Kununu\Elasticsearch\Query\Criteria\Filter\Term;
use Kununu\Elasticsearch\Query\Criteria\Filter\Terms;
use Kununu\Elasticsearch\Util\ConstantContainerTrait;

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
