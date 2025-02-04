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

    public const string EXISTS = Exists::KEYWORD;
    public const string PREFIX = Prefix::KEYWORD;
    public const string REGEXP = Regexp::KEYWORD;
    public const string TERM = Term::KEYWORD;
    public const string TERMS = Terms::KEYWORD;

    public const string LESS_THAN = 'lt';
    public const string GREATER_THAN = 'gt';
    public const string LESS_THAN_EQUALS = 'lte';
    public const string GREATER_THAN_EQUALS = 'gte';
    public const string BETWEEN = 'between';

    public const string GEO_DISTANCE = GeoDistance::KEYWORD;
    public const string GEO_SHAPE = GeoShape::KEYWORD;
}
