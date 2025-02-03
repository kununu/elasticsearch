<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

final class PrefixQuery
{
    public const string KEYWORD = 'prefix';

    public static function asArray(array $fields, $queryString, array $options = []): array
    {
        return [
            self::KEYWORD => [
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
