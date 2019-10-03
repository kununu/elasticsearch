<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Search;

/**
 * Class MatchPhrase
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Search
 */
class MatchPhrase
{
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
            'match_phrase' => [
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
