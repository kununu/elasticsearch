<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

final class Aggregation extends AbstractAggregation
{
    public static function create(string $field, string $type, string $name = '', array $options = []): self
    {
        return new self($field, $type, $name, $options);
    }

    public static function createGlobal(string $name = '', array $options = []): self
    {
        return new self('', self::GLOBAL, $name, $options);
    }

    public static function createFieldless(string $type, string $name = '', array $options = []): self
    {
        return new self(null, $type, $name, $options);
    }
}
