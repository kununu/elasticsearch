<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

interface AggregationResultInterface
{
    public function toArray(): array;

    public function getName(): string;

    public function getFields(): array;

    public function get(string $field): mixed;
}
