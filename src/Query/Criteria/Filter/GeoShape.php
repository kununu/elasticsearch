<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\GeoShapeInterface;

class GeoShape
{
    public const KEYWORD = 'geo_shape';

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
