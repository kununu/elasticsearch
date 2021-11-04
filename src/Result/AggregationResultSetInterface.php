<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

interface AggregationResultSetInterface
{
    public function setDocuments(ResultIteratorInterface $resultIterator): AggregationResultSetInterface;

    public function getDocuments(): ?ResultIteratorInterface;

    public function getResultByName(string $name): ?AggregationResultInterface;

    public function getResults(): array;
}
