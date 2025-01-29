<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

class Terms
{
    public const string KEYWORD = 'terms';

    public static function asArray(string $field, array $value, array $options = []): array
    {
        return [
            static::KEYWORD => array_merge($options, [$field => $value]),
        ];
    }
}
