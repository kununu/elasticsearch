<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

/**
 * Class MatchPhrasePrefix
 *
 * @package Kununu\Elasticsearch\Query\Criteria\Search
 */
class MatchPhrasePrefix
{
    public const KEYWORD = 'match_phrase_prefix';

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
