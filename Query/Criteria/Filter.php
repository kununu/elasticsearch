<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

use App\Services\Elasticsearch\Exception\QueryException;
use DateTime;
use InvalidArgumentException;

/**
 * Class Filter
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
class Filter implements FilterInterface
{
    /** @var string */
    protected $field;

    /** @var mixed */
    protected $value;

    /** @var string|null */
    protected $operator;

    /**
     * @param string      $field
     * @param mixed       $value
     * @param string|null $operator
     *
     * @throws \ReflectionException
     */
    public function __construct(string $field, $value, ?string $operator = null)
    {
        $this->field = $field;
        $this->value = $value;

        if ($operator !== null && !Operator::hasConstant($operator)) {
            throw new InvalidArgumentException('unknown operator "' . $operator . '" given');
        }

        $this->operator = $operator;
    }

    /**
     * @param string      $field
     * @param mixed       $value
     * @param string|null $operator
     *
     * @return static
     * @throws \ReflectionException
     */
    public static function create(string $field, $value, ?string $operator = null): self
    {
        return new static($field, $value, $operator);
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
        $preparedValue = $this->prepareDateTimeField($this->value);

        switch ($this->operator) {
            case Operator::TERM:
            case null:
                $filter = ['term' => [$this->field => $preparedValue]];
                break;
            case Operator::TERMS:
                $filter = ['terms' => [$this->field => $preparedValue]];
                break;
            case Operator::PREFIX:
                $filter = ['prefix' => [$this->field => $preparedValue]];
                break;
            case Operator::LESS_THAN:
                $filter = ['range' => [$this->field => ['lt' => $preparedValue]]];
                break;
            case Operator::LESS_THAN_EQUALS:
                $filter = ['range' => [$this->field => ['lte' => $preparedValue]]];
                break;
            case Operator::GREATER_THAN:
                $filter = ['range' => [$this->field => ['gt' => $preparedValue]]];
                break;
            case Operator::GREATER_THAN_EQUALS:
                $filter = ['range' => [$this->field => ['gte' => $preparedValue]]];
                break;
            case Operator::BETWEEN:
                $filter = ['range' => [$this->field => ['gte' => $preparedValue[0], 'lte' => $preparedValue[1]]]];
                break;
            case Operator::EXISTS:
                if ($this->value) {
                    $filter = ['exists' => ['field' => $this->field]];
                } else {
                    $filter = ['bool' => ['must_not' => [['exists' => ['field' => $this->field]]]]];
                }
                break;
            case Operator::REGEX:
                $filter = ['regexp' => [$this->field => $preparedValue]];
                break;
            case Operator::GEO_DISTANCE:
                if (!($preparedValue instanceof GeoDistanceInterface)) {
                    throw new InvalidArgumentException(
                        'Type of filter must be \App\Services\Elasticsearch\Query\Criteria\GeoDistanceInterface for geo_distance Queries.'
                    );
                }
                $filter = [
                    'geo_distance' => [
                        'distance' => $preparedValue->getDistance(),
                        $this->field => $preparedValue->getLocation(),
                    ],
                ];
                break;
            case Operator::GEO_SHAPE:
                if (!($preparedValue instanceof GeoShapeInterface)) {
                    throw new InvalidArgumentException(
                        'Type of filter must be \App\Services\Elasticsearch\Query\Criteria\GeoShapeInterface for geo_shape Queries.'
                    );
                }
                $filter = [
                    'geo_shape' => [
                        $this->field => [
                            'shape' => $preparedValue->toArray(),
                        ],
                    ],
                ];
                break;
            default:
                throw new QueryException('Unhandled operator "' . $this->operator . '"');
        }

        return $filter;
    }

    /**
     * Converts \DateTime to timestamps and returns every other $value as is.
     *
     * @param mixed $value
     *
     * @return int|mixed
     */
    protected function prepareDateTimeField($value)
    {
        return $value instanceof DateTime ? $value->getTimestamp() : $value;
    }
}
