<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation;

use Kununu\Elasticsearch\Util\ConstantContainerTrait;

final class Metric
{
    use ConstantContainerTrait;

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-avg-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/average/
     */
    public const string AVG = 'avg';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-cardinality-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/2.18/latest/aggregations/metric/cardinality/ OpenSearch documentation
     */
    public const string CARDINALITY = 'cardinality';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-extendedstats-aggregation.html  Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/extended-stats/ OpenSearch documentation
     */
    public const string EXTENDED_STATS = 'extended_stats';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-geobounds-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/geobounds/ OpenSearch documentation
     */
    public const string GEO_BOUNDS = 'geo_bounds';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-geocentroid-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/geocentroid/ OpenSearch documentation
     */
    public const string GEO_CENTROID = 'geo_centroid';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-max-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/maximum/ OpenSearch documentation
     */
    public const string MAX = 'max';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-min-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/minimum/ OpenSearch documentation
     */
    public const string MIN = 'min';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-percentile-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/percentile/ OpenSearch documentation
     */
    public const string PERCENTILES = 'percentiles';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-stats-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/stats/ OpenSearch documentation
     */
    public const string STATS = 'stats';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-sum-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/sum/ OpenSearch documentation
     */
    public const string SUM = 'sum';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-valuecount-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/metric/value-count/ OpenSearch documentation
     */
    public const string VALUE_COUNT = 'value_count';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-range-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/bucket/range/ OpenSearch documentation
     */
    public const string RANGE = 'range';
}
