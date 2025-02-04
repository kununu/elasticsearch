<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

final class MatchPhraseQuery
{
    public const string KEYWORD = 'match_phrase';

    public static function asArray(array $fields, string $queryString, array $options = []): array
    {
        return [
            self::KEYWORD => [
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
