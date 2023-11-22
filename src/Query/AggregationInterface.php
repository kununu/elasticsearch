<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

interface AggregationInterface
{
    public function toArray(): array;

    public function getName(): string;
}
