<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

final class Range
{
    public const string KEYWORD = 'range';

    public static function asArray(string $field, array $value, array $options = []): array
    {
        return [
            self::KEYWORD => [
                $field => array_merge(
                    $options,
                    $value
                ),
            ],
        ];
    }
}
