<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use RuntimeException;

final class MissingAggregationAttributesException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Aggregation name is missing');
    }
}
