<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Aggregation;

use App\Services\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class Bucket
 *
 * @package App\Services\Elasticsearch\Query\Aggregation
 */
final class Bucket
{
    use ConstantContainerTrait;

    public const TERMS = 'terms'; // https://www.elastic.co/guide/en/elasticsearch/reference/6.4/search-aggregations-bucket-terms-aggregation.html
}
