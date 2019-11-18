<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

/**
 * Class Range
 *
 * @package Kununu\Elasticsearch\Query\Criteria\Filter
 */
class Range
{
    public const KEYWORD = 'range';

    /**
     * @param string $field
     * @param array  $value
     * @param array  $options
     *
     * @return array
     */
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
