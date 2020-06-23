<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

/**
 * Class Term
 *
 * @package Kununu\Elasticsearch\Query\Criteria\Search
 */
class Term
{
    public const KEYWORD = 'term';

    /**
     * @param string $field
     * @param mixed  $term
     * @param array  $options
     *
     * @return array
     */
    public static function asArray(string $field, $term, array $options = []): array
    {
        return [
            static::KEYWORD => [
                $field => array_merge(
                    $options,
                    [
                        'value' => $term,
                    ]
                ),
            ],
        ];
    }
}
