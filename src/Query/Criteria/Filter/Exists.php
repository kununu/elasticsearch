<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Bool\MustNot;

final class Exists
{
    public const string KEYWORD = 'exists';

    public static function asArray(string $field, bool $value): array
    {
        $filter = [self::KEYWORD => ['field' => $field]];

        if (!$value) {
            $filter = [
                'bool' => [
                    MustNot::OPERATOR => [$filter],
                ],
            ];
        }

        return $filter;
    }
}
