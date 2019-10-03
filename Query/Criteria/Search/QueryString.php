<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Search;

/**
 * Class QueryString
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Search
 */
class QueryString
{
    use MultiFieldTrait;

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
            'query_string' => array_merge(
                $options,
                [
                    'fields' => self::prepareFields($fields),
                    'query' => $queryString,
                ]
            ),
        ];
    }
}
