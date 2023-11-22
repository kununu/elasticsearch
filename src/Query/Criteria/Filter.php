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

/**
 * Class Filter
 *
 * @package Kununu\Elasticsearch\Query\Criteria
 */
class Filter implements FilterInterface
{
    protected string $field;
    protected mixed $value;
    protected string|null $operator;
    protected array $options = [];

    /**
     * @param string      $field
     * @param mixed       $value
     * @param string|null $operator
     * @param array       $options
     */
    public function __construct(string $field, mixed $value, ?string $operator = null, array $options = [])
    {
        if ($operator !== null && !Operator::hasConstant($operator)) {
            throw new InvalidArgumentException('Unknown operator "' . $operator . '" given');
        }

        $this->field = $field;
        $this->value = $value;
        $this->operator = $operator;
        $this->options = $options;
    }

    /**
     * @param string      $field
     * @param mixed       $value
     * @param string|null $operator
     * @param array       $options
     *
     * @return \Kununu\Elasticsearch\Query\Criteria\Filter
     */
    public static function create(string $field, mixed $value, ?string $operator = null, array $options = []): Filter
    {
        return new static($field, $value, $operator, $options);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->mapOperator();
    }

    /**
     * @return array
     */
    protected function mapOperator(): array
    {
        return match ($this->operator) {
            Operator::TERM, null => Term::asArray($this->field, $this->value, $this->options),
            Operator::TERMS => Terms::asArray($this->field, $this->value, $this->options),
            Operator::PREFIX => Prefix::asArray($this->field, $this->value, $this->options),
            Operator::REGEXP => Regexp::asArray($this->field, $this->value, $this->options),
            Operator::LESS_THAN, Operator::LESS_THAN_EQUALS, Operator::GREATER_THAN, Operator::GREATER_THAN_EQUALS => Range::asArray(
                $this->field,
                [$this->operator => $this->value],
                $this->options
            ),
            Operator::BETWEEN => Range::asArray(
                $this->field,
                [
                    Operator::GREATER_THAN_EQUALS => $this->value[0],
                    Operator::LESS_THAN_EQUALS => $this->value[1],
                ],
                $this->options
            ),
            Operator::EXISTS => Exists::asArray($this->field, $this->value),
            Operator::GEO_DISTANCE => GeoDistance::asArray($this->field, $this->value, $this->options),
            Operator::GEO_SHAPE => GeoShape::asArray($this->field, $this->value, $this->options),
            default => throw new LogicException('Unhandled operator "' . $this->operator . '"'),
        };
    }
}
