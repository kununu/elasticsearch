<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

class AggregationResultSet implements AggregationResultSetInterface
{
    protected ResultIterator|null $documents = null;
    protected array $aggregationResults = [];

    /**
     * @param array $rawResult
     */
    public function __construct(array $rawResult = [])
    {
        foreach ($rawResult as $aggregationName => $fields) {
            $this->aggregationResults[$aggregationName] = AggregationResult::create($aggregationName, $fields);
        }
    }

    public static function create(array $rawResult = []): AggregationResultSet
    {
        return new static($rawResult);
    }

    public function setDocuments(ResultIteratorInterface $resultIterator): AggregationResultSetInterface
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
