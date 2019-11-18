<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Result;

/**
 * Class AggregationResult
 *
 * @package App\Services\Elasticsearch\Result
 */
class AggregationResult implements AggregationResultInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @param string $name
     * @param array  $rawResult
     */
    public function __construct(string $name, array $rawResult = [])
    {
        $this->name = $name;
        $this->fields = $rawResult;
    }

    /**
     * @param string $name
     * @param array  $rawResult
     *
     * @return \App\Services\Elasticsearch\Result\AggregationResult
     */
    public static function create(string $name, array $rawResult): AggregationResult
    {
        return new static($name, $rawResult);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            $this->name => $this->fields,
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param string $field
     *
     * @return mixed|null
     */
    public function get(string $field)
    {
        return $this->fields[$field] ?? null;
    }

    /**
     * Shortcut for bucket aggregations to directly retrieve the buckets list.
     *
     * @return array|null
     */
    public function getBuckets(): ?array
    {
        return $this->get('buckets');
    }

    /**
     * Shortcut method for single-value numeric metrics aggregations to directly retrieve the value field.
     *
     * @return float|null
     */
    public function getValue(): ?float
    {
        return $this->get('value');
    }
}
