<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\GeoDistanceInterface;

final class GeoDistance
{
    public const string KEYWORD = 'geo_distance';

    public static function asArray(string $field, GeoDistanceInterface $value, array $options = []): array
    {
        return [
            self::KEYWORD => array_merge(
                $options,
                [
                    'distance' => $value->getDistance(),
                    $field     => $value->getLocation(),
                ]
            ),
        ];
    }
}
