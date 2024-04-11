<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Filter\Exists;
use Kununu\Elasticsearch\Query\Criteria\Filter\GeoDistance;
use Kununu\Elasticsearch\Query\Criteria\Filter\GeoShape;
use Kununu\Elasticsearch\Query\Criteria\Filter\Prefix;
use Kununu\Elasticsearch\Query\Criteria\Filter\Range;
use Kununu\Elasticsearch\Query\Criteria\Filter\Regexp;
use Kununu\Elasticsearch\Query\Criteria\Filter\Term;
use Kununu\Elasticsearch\Query\Criteria\Filter\Terms;
use LogicException;

class Filter implements FilterInterface
{
    public function __construct(
        protected readonly string $field,
        protected readonly mixed $value,
        protected readonly ?string $operator = null,
        protected readonly array $options = []
    ) {
        if ($operator !== null && !Operator::hasConstant($operator)) {
            throw new InvalidArgumentException('Unknown operator "' . $operator . '" given');
        }
    }

    public static function create(string $field, mixed $value, ?string $operator = null, array $options = []): Filter
    {
        return new static($field, $value, $operator, $options);
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
            default                       => throw new LogicException('Unhandled operator "' . $this->operator . '"'),
        };
    }
}
