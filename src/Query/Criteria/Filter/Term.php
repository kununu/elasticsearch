<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

final class Term
{
    public const string KEYWORD = 'term';

    public static function asArray(string $field, $value, array $options = []): array
    {
        return empty($options)
            ? [self::KEYWORD => [$field => $value]]
            : [self::KEYWORD => [$field => array_merge($options, ['value' => $value])]];
    }
}
