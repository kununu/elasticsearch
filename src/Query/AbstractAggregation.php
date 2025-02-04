<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use Kununu\Elasticsearch\Exception\UnknownAggregationTypeException;
use Kununu\Elasticsearch\Query\Aggregation\Bucket;
use Kununu\Elasticsearch\Query\Aggregation\Metric;
use stdClass;

abstract class AbstractAggregation implements AggregationInterface
{
    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-global-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/bucket/global/ OpenSearch documentation
     */
    public const string GLOBAL = 'global';

    protected readonly string $name;
    /** @var array<AggregationInterface> */
    protected array $nestedAggregations = [];

    public function __construct(
        protected readonly ?string $field,
        protected readonly string $type,
        string $name = '',
        protected readonly array $options = [],
    ) {
        if (!Metric::hasConstant($type) && !Bucket::hasConstant($type) && $type !== static::GLOBAL) {
            throw new UnknownAggregationTypeException($type);
        }

        $this->name = empty($name) ? spl_object_hash($this) : $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function nest(AggregationInterface $aggregation): static
    {
        $this->nestedAggregations[] = $aggregation;

        return $this;
    }

    public function toArray(): array
    {
        if ($this->type === static::GLOBAL) {
            $body = [
                $this->name => array_merge(
                    ['global' => new stdClass()],
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
                fn(array $carry, AggregationInterface $aggregation): array => array_merge(
                    $carry,
                    $aggregation->toArray()
                ),
                []
            );
        }

        return $body;
    }
}
