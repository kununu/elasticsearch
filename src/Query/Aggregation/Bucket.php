<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation;

use Kununu\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class Bucket
 *
 * @package Kununu\Elasticsearch\Query\Aggregation
 */
final class Bucket
{
    use ConstantContainerTrait;

    public const TERMS = 'terms'; // https://www.elastic.co/guide/en/elasticsearch/reference/7.9/search-aggregations-bucket-terms-aggregation.html
    public const FILTERS = 'filters'; // https://www.elastic.co/guide/en/elasticsearch/reference/7.9/search-aggregations-bucket-filters-aggregation.html
}
