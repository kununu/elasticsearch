<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

class Range
{
    public const KEYWORD = 'range';

    public static function asArray(string $field, array $value, array $options = []): array
    {
        return [
            static::KEYWORD => [
                $field => array_merge(
                    $options,
                    $value
                ),
            ],
        ];
    }
}
