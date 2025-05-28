<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

final class WildcardQuery
{
    public const string KEYWORD = 'wildcard';

    public static function asArray(string $field, $term, array $options = []): array
    {
        return [
            self::KEYWORD => [
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
