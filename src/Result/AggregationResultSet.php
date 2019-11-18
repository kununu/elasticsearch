<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

/**
 * Class AggregationResultSet
 *
 * @package Kununu\Elasticsearch\Result
 */
class AggregationResultSet implements AggregationResultSetInterface
{
    /**
     * @var \Kununu\Elasticsearch\Result\ResultIterator
     */
    protected $documents;

    /**
     * @var \Kununu\Elasticsearch\Result\AggregationResult[]
     */
    protected $aggregationResults = [];

    /**
     * @param array $rawResult
     */
    public function __construct(array $rawResult = [])
    {
        foreach ($rawResult as $aggregationName => $fields) {
            $this->aggregationResults[$aggregationName] = AggregationResult::create($aggregationName, $fields);
        }
    }

    /**
     * @param array $rawResult
     *
     * @return \Kununu\Elasticsearch\Result\AggregationResultSet
     */
    public static function create(array $rawResult = []): AggregationResultSet
    {
        return new static($rawResult);
    }

    /**
     * @param \Kununu\Elasticsearch\Result\ResultIteratorInterface $resultIterator
     *
     * @return \Kununu\Elasticsearch\Result\AggregationResultSetInterface
     */
    public function setDocuments(ResultIteratorInterface $resultIterator): AggregationResultSetInterface
    {
        $this->documents = $resultIterator;

        return $this;
    }

    /**
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface|null
     */
    public function getDocuments(): ?ResultIteratorInterface
    {
        return $this->documents;
    }

    /**
     * @param string $name
     *
     * @return \Kununu\Elasticsearch\Result\AggregationResultInterface|null
     */
    public function getResultByName(string $name): ?AggregationResultInterface
    {
        return $this->aggregationResults[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->aggregationResults;
    }
}
