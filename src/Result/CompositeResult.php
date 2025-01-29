<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

final readonly class CompositeResult
{
    public function __construct(
        public array $results,
        public int $documentsCount,
        public string $aggregationName,
    ) {
    }
}
