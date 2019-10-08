<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Query\Aggregation\Bucket;
use App\Services\Elasticsearch\Query\Aggregation\Metric;
use InvalidArgumentException;

/**
 * Class Aggregation
 *
 * @package App\Services\Elasticsearch\Query
 */
class Aggregation implements AggregationInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $type;

    /** @var string */
    protected $field;

    /** @var array */
    protected $options = [];

    /** @var AggregationInterface[] */
    protected $nestedAggregations = [];

    /**
     * @param string $field
     * @param string $type
     * @param string $name
     * @param array  $options
     *
     * @throws \ReflectionException
     */
    public function __construct(string $field, string $type, string $name = '', array $options = [])
    {
        if (!Metric::hasConstant($type) && !Bucket::hasConstant($type)) {
            throw new InvalidArgumentException('unknown type "' . $type . '" given');
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
     * @return \App\Services\Elasticsearch\Query\Aggregation
     * @throws \ReflectionException
     */
    public static function create(string $field, string $type, string $name = '', array $options = [])
    {
        return new self($field, $type, $name, $options);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param \App\Services\Elasticsearch\Query\AggregationInterface $aggregation
     *
     * @return \App\Services\Elasticsearch\Query\AggregationInterface
     */
    public function nest(AggregationInterface $aggregation): AggregationInterface
    {
        $this->nestedAggregations[] = $aggregation;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $body = [
            $this->name => [
                $this->type => array_merge(
                    ['field' => $this->field],
                    $this->options
                ),
            ],
        ];

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
