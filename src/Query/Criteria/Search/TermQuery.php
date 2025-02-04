<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

final class TermQuery
{
    public const string KEYWORD = 'term';

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
