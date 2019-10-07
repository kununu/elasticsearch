<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Aggregation;

use App\Services\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class Metric
 *
 * @package App\Services\Elasticsearch\Query\Aggregation
 */
final class Metric
{
    use ConstantContainerTrait;

    public const AVERAGE = 'avg';
    public const CARDINALITY = 'cardinality';
    public const EXTENDED_STATS = 'extended_stats';
    public const GEO_BOUNDS = 'geo_bounds';
    public const GEO_CENTROID = 'geo_centroid';
    public const MAX = 'max';
    public const MIN = 'min';
    public const PERCENTILES = 'percentiles';
    public const STATS = 'stats';
    public const SUM = 'sum';
    public const VALUE_COUNT = 'value_count';
}
