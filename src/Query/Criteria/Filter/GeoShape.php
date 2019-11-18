<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Filter;

use App\Services\Elasticsearch\Query\Criteria\GeoShapeInterface;

/**
 * Class GeoShape
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Filter
 */
class GeoShape
{
    public const KEYWORD = 'geo_shape';

    /**
     * @param string                                                       $field
     * @param \App\Services\Elasticsearch\Query\Criteria\GeoShapeInterface $value
     * @param array                                                        $options
     *
     * @return array
     */
    public static function asArray(string $field, GeoShapeInterface $value, array $options = []): array
    {
        return [
            static::KEYWORD => [
                $field => array_merge(
                    $options,
                    ['shape' => $value->toArray()]
                ),
            ],
        ];
    }
}
