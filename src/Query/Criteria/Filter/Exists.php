<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Bool\MustNot;

class Exists
{
    public const KEYWORD = 'exists';

    public static function asArray(string $field, bool $value): array
    {
        $filter = [static::KEYWORD => ['field' => $field]];

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
