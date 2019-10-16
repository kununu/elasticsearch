<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Filter;

use App\Services\Elasticsearch\Query\Criteria\GeoDistanceInterface;

/**
 * Class GeoDistance
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Filter
 */
class GeoDistance
{
    public const KEYWORD = 'geo_distance';

    /**
     * @param string                                                          $field
     * @param \App\Services\Elasticsearch\Query\Criteria\GeoDistanceInterface $value
     * @param array                                                           $options
     *
     * @return array
     */
    public static function asArray(string $field, GeoDistanceInterface $value, array $options = []): array
    {
        return [
            static::KEYWORD => array_merge(
                $options,
                [
                    'distance' => $value->getDistance(),
                    $field => $value->getLocation(),
                ]
            ),
        ];
    }
}
