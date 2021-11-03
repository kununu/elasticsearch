<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

/**
 * Interface GeoShapeInterface
 *
 * @package Kununu\Elasticsearch\Query\Criteria
 */
interface GeoShapeInterface
{
    /**
     * @return array
     *
     * Available shapes: https://www.elastic.co/guide/en/elasticsearch/reference/7.9/geo-shape.html#input-structure
     */
    public function toArray();
}
