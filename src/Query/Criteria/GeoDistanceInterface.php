<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

/**
 * Interface GeoDistanceInterface
 *
 * @package Kununu\Elasticsearch\Query\Criteria
 */
interface GeoDistanceInterface
{
    /**
     * Accepted formats:
     * a) Lat Lon As Array: Format in [lon, lat], note, the order of lon/lat here in order to conform with GeoJSON.
     * b) Lat Lon as associative array: ['lat' => 0, 'lon' => 0]
     *
     * @return array
     */
    public function getLocation(): array;

    /**
     * Accepted units:
     * https://www.elastic.co/guide/en/elasticsearch/reference/7.9/common-options.html#distance-units
     *
     * @return string
     */
    public function getDistance(): string;
}
