<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

class Regexp
{
    public const KEYWORD = 'regexp';

    public static function asArray(string $field, $value, array $options = []): array
    {
        return empty($options)
            ? [static::KEYWORD => [$field => $value]]
            : [static::KEYWORD => [$field => array_merge($options, ['value' => $value])]];
    }
}
