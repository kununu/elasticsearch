<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Result;

/**
 * Interface AggregationResultSetInterface
 *
 * @package App\Services\Elasticsearch\Result
 */
interface AggregationResultSetInterface
{
    /**
     * @param \App\Services\Elasticsearch\Result\ResultIteratorInterface $resultIterator
     *
     * @return \App\Services\Elasticsearch\Result\AggregationResultSetInterface
     */
    public function setDocuments(ResultIteratorInterface $resultIterator): AggregationResultSetInterface;

    /**
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface|null
     */
    public function getDocuments(): ?ResultIteratorInterface;

    /**
     * @param string $name
     *
     * @return \App\Services\Elasticsearch\Result\AggregationResult|null
     */
    public function getResultByName(string $name): ?AggregationResultInterface;

    /**
     * @return array
     */
    public function getResults(): array;
}
