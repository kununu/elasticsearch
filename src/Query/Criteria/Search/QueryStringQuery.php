<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

class QueryStringQuery
{
    use MultiFieldTrait;

    public const string KEYWORD = 'query_string';

    public static function asArray(array $fields, string $queryString, array $options = []): array
    {
        return [
            static::KEYWORD => array_merge(
                $options,
                [
                    'fields' => self::prepareFields($fields),
                    'query'  => $queryString,
                ]
            ),
        ];
    }
}
