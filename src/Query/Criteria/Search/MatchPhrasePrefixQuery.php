<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

class MatchPhrasePrefixQuery
{
    public const KEYWORD = 'match_phrase_prefix';

    public static function asArray(array $fields, string $queryString, array $options = []): array
    {
        return [
            static::KEYWORD => [
                $fields[0] => array_merge(
                    $options,
                    [
                        'query' => $queryString,
                    ]
                ),
            ],
        ];
    }
}
