<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

final class Terms
{
    public const string KEYWORD = 'terms';

    public static function asArray(string $field, array $value, array $options = []): array
    {
        return [
            self::KEYWORD => array_merge($options, [$field => $value]),
        ];
    }
}
