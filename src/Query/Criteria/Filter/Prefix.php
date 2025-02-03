<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

final class Prefix
{
    public const string KEYWORD = 'prefix';

    public static function asArray(string $field, $value, array $options = []): array
    {
        return [
            self::KEYWORD => array_merge($options, [$field => $value]),
        ];
    }
}
