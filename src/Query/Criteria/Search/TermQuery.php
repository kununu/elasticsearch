<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

class TermQuery
{
    public const KEYWORD = 'term';

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
