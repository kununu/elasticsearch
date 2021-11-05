<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Aggregation\Bucket;
use Kununu\Elasticsearch\Query\Aggregation\Metric;

class Aggregation implements AggregationInterface
{
    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-global-aggregation.html
     */
    public const GLOBAL = 'global';

    protected string $name;
    protected string $type;
    protected string|null $field;
    protected array $options = [];
    /**
     * @var \Kununu\Elasticsearch\Query\AggregationInterface[]
     */
    protected array $nestedAggregations = [];

    public function __construct(?string $field, string $type, string $name = '', array $options = [])
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

    public static function create(string $field, string $type, string $name = '', array $options = []): Aggregation
    {
        return new self($field, $type, $name, $options);
    }

    public static function createGlobal(string $name = '', array $options = []): Aggregation
    {
        return new self('', static::GLOBAL, $name, $options);
    }

    public static function createFieldless(string $type, string $name = '', array $options = []): Aggregation
    {
        return new self(null, $type, $name, $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function nest(AggregationInterface $aggregation): Aggregation
    {
        $this->nestedAggregations[] = $aggregation;

        return $this;
    }

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
                        $this->field ? ['field' => $this->field] : [],
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
