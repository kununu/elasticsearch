<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation;

use Kununu\Elasticsearch\Util\ConstantContainerTrait;

final class Bucket
{
    use ConstantContainerTrait;

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/search-aggregations-bucket-terms-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/bucket/terms/ OpenSearch documentation
     */
    public const string TERMS = 'terms';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/search-aggregations-bucket-filters-aggregation.html Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/aggregations/bucket/filters/ OpenSearch documentation
     */
    public const string FILTERS = 'filters';
}
