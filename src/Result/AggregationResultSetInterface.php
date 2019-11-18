<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

/**
 * Interface AggregationResultSetInterface
 *
 * @package Kununu\Elasticsearch\Result
 */
interface AggregationResultSetInterface
{
    /**
     * @param \Kununu\Elasticsearch\Result\ResultIteratorInterface $resultIterator
     *
     * @return \Kununu\Elasticsearch\Result\AggregationResultSetInterface
     */
    public function setDocuments(ResultIteratorInterface $resultIterator): AggregationResultSetInterface;

    /**
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface|null
     */
    public function getDocuments(): ?ResultIteratorInterface;

    /**
     * @param string $name
     *
     * @return \Kununu\Elasticsearch\Result\AggregationResult|null
     */
    public function getResultByName(string $name): ?AggregationResultInterface;

    /**
     * @return array
     */
    public function getResults(): array;
}
