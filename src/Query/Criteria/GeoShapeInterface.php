<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

/**
 * Interface GeoShapeInterface
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
interface GeoShapeInterface
{
    /**
     * @return array
     *
     * Available shapes: https://www.elastic.co/guide/en/elasticsearch/reference/6.4/geo-shape.html#input-structure
     */
    public function toArray();
}
