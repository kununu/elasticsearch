<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Search;

/**
 * Class Match
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Search
 */
class Match
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
        if (count($fields) > 1) {
            $query = [
                'multi_match' => array_merge(
                    $options,
                    [
                        'fields' => self::prepareFields($fields),
                        'query' => $queryString,
                    ]
                ),
            ];
        } else {
            $query = [
                'match' => [
                    $fields[0] => array_merge(
                        $options,
                        [
                            'query' => $queryString,
                        ]
                    ),
                ],
            ];
        }

        return $query;
    }
}
