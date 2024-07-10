<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation;

final class SourceProperty
{
    public function __construct(
        public readonly string $source,
        public readonly string $property,
        public readonly bool $missingBucket = false
    )
    {
    }
}
