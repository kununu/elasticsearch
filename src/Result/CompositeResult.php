<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

final class CompositeResult
{
    public function __construct(
        public readonly array $results,
        public readonly int $documentsCount,
        public readonly string $aggregationName
    )
    {
    }
}
