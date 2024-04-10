<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

interface GeoShapeInterface
{
    /**
     * Available shapes: https://www.elastic.co/guide/en/elasticsearch/reference/7.9/geo-shape.html#input-structure
     */
    public function toArray(): array;
}
