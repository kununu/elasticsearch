<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation;

use Kununu\Elasticsearch\Util\ConstantContainerTrait;

final class Metric
{
    use ConstantContainerTrait;

    public const AVG = 'avg'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-avg-aggregation.html
    public const CARDINALITY = 'cardinality'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-cardinality-aggregation.html
    public const EXTENDED_STATS = 'extended_stats'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-extendedstats-aggregation.html
    public const GEO_BOUNDS = 'geo_bounds'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-geobounds-aggregation.html
    public const GEO_CENTROID = 'geo_centroid'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-geocentroid-aggregation.html
    public const MAX = 'max'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-max-aggregation.html
    public const MIN = 'min'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-min-aggregation.html
    public const PERCENTILES = 'percentiles'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-percentile-aggregation.html
    public const STATS = 'stats'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-stats-aggregation.html
    public const SUM = 'sum'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-sum-aggregation.html
    public const VALUE_COUNT = 'value_count'; // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-valuecount-aggregation.html
    public const RANGE = 'range'; //https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-range-aggregation.html
}
