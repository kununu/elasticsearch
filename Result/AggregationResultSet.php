<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Result;

/**
 * Class AggregationResultSet
 *
 * @package App\Services\Elasticsearch\Result
 */
class AggregationResultSet implements AggregationResultSetInterface
{
    /** @var \App\Services\Elasticsearch\Result\ResultIterator */
    protected $documents;

    /** @var \App\Services\Elasticsearch\Result\AggregationResult[] */
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
     * @return \App\Services\Elasticsearch\Result\AggregationResultSet
     */
    public static function create(array $rawResult): AggregationResultSet
    {
        return new static($rawResult);
    }

    /**
     * @param \App\Services\Elasticsearch\Result\ResultIteratorInterface $resultIterator
     *
     * @return \App\Services\Elasticsearch\Result\AggregationResultSetInterface
     */
    public function setDocuments(ResultIteratorInterface $resultIterator): AggregationResultSetInterface
    {
        $this->documents = $resultIterator;

        return $this;
    }

    /**
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface|null
     */
    public function getDocuments(): ?ResultIteratorInterface
    {
        return $this->documents;
    }

    /**
     * @param string $name
     *
     * @return \App\Services\Elasticsearch\Result\AggregationResultInterface|null
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
