<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

abstract class AbstractAggregationResult implements AggregationResultInterface
{
    public function __construct(protected readonly string $name, protected readonly array $fields = [])
    {
    }

    public function toArray(): array
    {
        return [
            $this->name => $this->fields,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function get(string $field): mixed
    {
        return $this->fields[$field] ?? null;
    }

    /**
     * Shortcut for bucket aggregations to directly retrieve the buckets list.
     */
    public function getBuckets(): ?array
    {
        return $this->get('buckets');
    }

    /**
     * Shortcut method for single-value numeric metrics aggregations to directly retrieve the value field.
     */
    public function getValue(): ?float
    {
        return $this->get('value');
    }
}
