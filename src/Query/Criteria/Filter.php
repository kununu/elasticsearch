<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

final class Filter extends AbstractFilter
{
    public static function create(string $field, mixed $value, ?string $operator = null, array $options = []): Filter
    {
        return new self($field, $value, $operator, $options);
    }
}
