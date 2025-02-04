<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

interface GeoShapeInterface
{
    /**
     * Available shapes:
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/geo-shape.html#input-structure Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/field-types/supported-field-types/geo-shape/ OpenSearch documentation
     */
    public function toArray(): array;
}
