<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

/**
 * Class QueryString
 *
 * @package Kununu\Elasticsearch\Query\Criteria\Search
 */
class QueryString
{
    use MultiFieldTrait;

    public const KEYWORD = 'query_string';

    /**
     * @param array  $fields
     * @param string $queryString
     * @param array  $options
     *
     * @return array
     */
    public static function asArray(array $fields, string $queryString, array $options = []): array
    {
        return [
            static::KEYWORD => array_merge(
                $options,
                [
                    'fields' => self::prepareFields($fields),
                    'query' => $queryString,
                ]
            ),
        ];
    }
}
