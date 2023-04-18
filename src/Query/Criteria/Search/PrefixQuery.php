<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

class PrefixQuery
{
    public const KEYWORD = 'prefix';

    public static function asArray(array $fields, $queryString, array $options = []): array
    {
        return [
            static::KEYWORD => [
                $fields[0] => array_merge(
                    $options,
                    [
                        'value' => $queryString,
                    ]
                ),
            ],
        ];
    }
}