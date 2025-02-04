<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation;

final readonly class SourceProperty
{
    public function __construct(
        public string $source,
        public string $property,
        public bool $missingBucket = false,
    ) {
    }
}
