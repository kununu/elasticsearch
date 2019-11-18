<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

/**
 * Class Prefix
 *
 * @package Kununu\Elasticsearch\Query\Criteria\Filter
 */
class Prefix
{
    public const KEYWORD = 'prefix';

    /**
     * @param string $field
     * @param mixed  $value
     * @param array  $options
     *
     * @return array
     */
    public static function asArray(string $field, $value, array $options = []): array
    {
        return [
            static::KEYWORD => array_merge($options, [$field => $value]),
        ];
    }
}
