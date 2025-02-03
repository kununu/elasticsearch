<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

use Kununu\Elasticsearch\Exception\UnhandledOperatorException;
use Kununu\Elasticsearch\Exception\UnknownOperatorException;
use Kununu\Elasticsearch\Query\Criteria\Filter\Exists;
use Kununu\Elasticsearch\Query\Criteria\Filter\GeoDistance;
use Kununu\Elasticsearch\Query\Criteria\Filter\GeoShape;
use Kununu\Elasticsearch\Query\Criteria\Filter\Prefix;
use Kununu\Elasticsearch\Query\Criteria\Filter\Range;
use Kununu\Elasticsearch\Query\Criteria\Filter\Regexp;
use Kununu\Elasticsearch\Query\Criteria\Filter\Term;
use Kununu\Elasticsearch\Query\Criteria\Filter\Terms;

abstract class AbstractFilter implements FilterInterface
{
    public function __construct(
        protected readonly string $field,
        protected readonly mixed $value,
        protected readonly ?string $operator = null,
        protected readonly array $options = [],
    ) {
        if ($operator !== null && !Operator::hasConstant($operator)) {
            throw new UnknownOperatorException($this->operator);
        }
    }

    public function toArray(): array
    {
        return $this->mapOperator();
    }

    protected function mapOperator(): array
    {
        return match ($this->operator) {
            Operator::TERM, null          => Term::asArray(
                $this->field,
                $this->value,
                $this->options
            ),
            Operator::TERMS               => Terms::asArray(
                $this->field,
                $this->value,
                $this->options
            ),
            Operator::PREFIX              => Prefix::asArray(
                $this->field,
                $this->value,
                $this->options
            ),
            Operator::REGEXP              => Regexp::asArray(
                $this->field,
                $this->value,
                $this->options
            ),
            Operator::LESS_THAN,
            Operator::LESS_THAN_EQUALS,
            Operator::GREATER_THAN,
            Operator::GREATER_THAN_EQUALS => Range::asArray(
                $this->field,
                [$this->operator => $this->value],
                $this->options
            ),
            Operator::BETWEEN             => Range::asArray(
                $this->field,
                [
                    Operator::GREATER_THAN_EQUALS => $this->value[0],
                    Operator::LESS_THAN_EQUALS    => $this->value[1],
                ],
                $this->options
            ),
            Operator::EXISTS              => Exists::asArray(
                $this->field,
                $this->value
            ),
            Operator::GEO_DISTANCE        => GeoDistance::asArray(
                $this->field,
                $this->value,
                $this->options
            ),
            Operator::GEO_SHAPE           => GeoShape::asArray(
                $this->field,
                $this->value,
                $this->options
            ),
            default                       => throw new UnhandledOperatorException($this->operator),
        };
    }
}
