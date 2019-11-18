<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Filter;

/**
 * Class Terms
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Filter
 */
class Terms
{
    public const KEYWORD = 'terms';

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
            static::KEYWORD => array_merge($options, [$field => $value]),
        ];
    }
}
