<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Aggregation\Bucket;
use Kununu\Elasticsearch\Query\Aggregation\Metric;

/**
 * Class Aggregation
 *
 * @package Kununu\Elasticsearch\Query
 */
class Aggregation implements AggregationInterface
{
    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-global-aggregation.html
     */
    public const GLOBAL = 'global';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var \Kununu\Elasticsearch\Query\AggregationInterface[]
     */
    protected $nestedAggregations = [];

    /**
     * @param string $field
     * @param string $type
     * @param string $name
     * @param array  $options
     */
    public function __construct(string $field, string $type, string $name = '', array $options = [])
    {
        if (!Metric::hasConstant($type) && !Bucket::hasConstant($type) && $type !== static::GLOBAL) {
            throw new InvalidArgumentException('Unknown type "' . $type . '" given');
        }

        if (empty($name)) {
            $name = spl_object_hash($this);
        }

        $this->name = $name;
        $this->type = $type;
        $this->field = $field;
        $this->options = $options;
    }

    /**
     * @param string $field
     * @param string $type
     * @param string $name
     * @param array  $options
     *
     * @return \Kununu\Elasticsearch\Query\Aggregation
     */
    public static function create(string $field, string $type, string $name = '', array $options = []): Aggregation
    {
        return new self($field, $type, $name, $options);
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return \Kununu\Elasticsearch\Query\Aggregation
     */
    public static function createGlobal(string $name = '', array $options = []): Aggregation
    {
        return new self('', static::GLOBAL, $name, $options);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param \Kununu\Elasticsearch\Query\AggregationInterface $aggregation
     *
     * @return \Kununu\Elasticsearch\Query\Aggregation
     */
    public function nest(AggregationInterface $aggregation): Aggregation
    {
        $this->nestedAggregations[] = $aggregation;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        if ($this->type === static::GLOBAL) {
            $body = [
                $this->name => array_merge(
                    ['global' => new \stdClass()],
                    $this->options
                ),
            ];
        } else {
            $body = [
                $this->name => [
                    $this->type => array_merge(
                        ['field' => $this->field],
                        $this->options
                    ),
                ],
            ];
        }

        if (count($this->nestedAggregations) > 0) {
            $body[$this->name]['aggs'] = array_reduce(
                $this->nestedAggregations,
                function (array $carry, AggregationInterface $aggregation): array {
                    return array_merge($carry, $aggregation->toArray());
                },
                []
            );
        }

        return $body;
    }
}
