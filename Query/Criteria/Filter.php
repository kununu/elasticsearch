<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

use App\Services\Elasticsearch\Exception\QueryException;
use App\Services\Elasticsearch\Query\Criteria\Filter\Exists;
use App\Services\Elasticsearch\Query\Criteria\Filter\GeoDistance;
use App\Services\Elasticsearch\Query\Criteria\Filter\GeoShape;
use App\Services\Elasticsearch\Query\Criteria\Filter\Prefix;
use App\Services\Elasticsearch\Query\Criteria\Filter\Range;
use App\Services\Elasticsearch\Query\Criteria\Filter\Regexp;
use App\Services\Elasticsearch\Query\Criteria\Filter\Term;
use App\Services\Elasticsearch\Query\Criteria\Filter\Terms;
use InvalidArgumentException;

/**
 * Class Filter
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
class Filter implements FilterInterface
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string|null
     */
    protected $operator;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param string      $field
     * @param mixed       $value
     * @param string|null $operator
     * @param array       $options
     */
    public function __construct(string $field, $value, ?string $operator = null, array $options = [])
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
     * @return \App\Services\Elasticsearch\Query\Criteria\Filter
     */
    public static function create(string $field, $value, ?string $operator = null, array $options = []): Filter
    {
        return new static($field, $value, $operator, $options);
    }

    /**
     * @return array
     * @throws \App\Services\Elasticsearch\Exception\QueryException
     */
    public function toArray(): array
    {
        return $this->mapOperator();
    }

    /**
     * @return array
     * @throws \App\Services\Elasticsearch\Exception\QueryException
     */
    protected function mapOperator(): array
    {
        switch ($this->operator) {
            case Operator::TERM:
            case null:
                $filter = Term::asArray($this->field, $this->value, $this->options);
                break;
            case Operator::TERMS:
                $filter = Terms::asArray($this->field, $this->value, $this->options);
                break;
            case Operator::PREFIX:
                $filter = Prefix::asArray($this->field, $this->value, $this->options);
                break;
            case Operator::REGEXP:
                $filter = Regexp::asArray($this->field, $this->value, $this->options);
                break;
                break;
            case Operator::LESS_THAN:
            case Operator::LESS_THAN_EQUALS:
            case Operator::GREATER_THAN:
            case Operator::GREATER_THAN_EQUALS:
                $filter = Range::asArray($this->field, [$this->operator => $this->value], $this->options);
                break;
            case Operator::BETWEEN:
                $filter = Range::asArray(
                    $this->field,
                    [
                        Operator::GREATER_THAN_EQUALS => $this->value[0],
                        Operator::LESS_THAN_EQUALS => $this->value[1],
                    ],
                    $this->options
                );
                break;
            case Operator::EXISTS:
                $filter = Exists::asArray($this->field, $this->value);
                break;
            case Operator::GEO_DISTANCE:
                $filter = GeoDistance::asArray($this->field, $this->value, $this->options);
                break;
            case Operator::GEO_SHAPE:
                $filter = GeoShape::asArray($this->field, $this->value, $this->options);
                break;
            default:
                throw new QueryException('Unhandled operator "' . $this->operator . '"');
        }

        return $filter;
    }
}
