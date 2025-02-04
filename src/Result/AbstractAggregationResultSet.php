<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

abstract class AbstractAggregationResultSet implements AggregationResultSetInterface
{
    protected ?ResultIteratorInterface $documents = null;
    protected array $aggregationResults = [];

    public function __construct(array $rawResult = [])
    {
        foreach ($rawResult as $aggregationName => $fields) {
            $this->aggregationResults[$aggregationName] = AggregationResult::create($aggregationName, $fields);
        }
    }

    public function setDocuments(ResultIteratorInterface $resultIterator): static
    {
        $this->documents = $resultIterator;

        return $this;
    }

    public function getDocuments(): ?ResultIteratorInterface
    {
        return $this->documents;
    }

    public function getResultByName(string $name): ?AggregationResultInterface
    {
        return $this->aggregationResults[$name] ?? null;
    }

    public function getResults(): array
    {
        return $this->aggregationResults;
    }
}
