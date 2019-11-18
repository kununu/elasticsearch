<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Filter;

use App\Services\Elasticsearch\Query\Criteria\Bool\MustNot;

/**
 * Class Exists
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Filter
 */
class Exists
{
    public const KEYWORD = 'exists';

    /**
     * @param string $field
     * @param bool   $value
     *
     * @return array
     */
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
