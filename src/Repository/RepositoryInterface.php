<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Generator;
use Kununu\Elasticsearch\Query\CompositeAggregationQueryInterface;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\AggregationResultSetInterface;
use Kununu\Elasticsearch\Result\ResultIteratorInterface;

interface RepositoryInterface
{
    /**
     * This method indexes the given $entity, i.e. it either inserts or replaces the whole document.
     */
    public function save(string $id, array|object $entity): void;

    /**
     * This method indexes the given array of $entities, i.e. it either inserts or replaces the whole documents.
     *
     * @param array[]|object[] $entities Associative array with document IDs as keys and documents as values
     */
    public function saveBulk(array $entities): void;

    /**
     * This method uses the _update API with doc_as_upsert option to persist the given $entity,
     * i.e. it either inserts the whole document or updates an existing document partially
     * (with what's present on $entity)
     */
    public function upsert(string $id, array|object $entity): void;

    /**
     * This method uses the _update API to update the entity with given $id,
     * i.e. it overrides all attributes present in $partialEntity
     */
    public function update(string $id, array|object $partialEntity): void;

    /**
     * This method deletes a single document with given $id.
     */
    public function delete(string $id): void;

    /**
     * This method deletes all documents matching the given $query.
     */
    public function deleteByQuery(QueryInterface $query, bool $proceedOnConflicts = false): array;

    /**
     * This method bulk deletes all documents matching the given $ids.
     */
    public function deleteBulk(string ...$ids): void;

    /**
     * This method retrieves all documents matching the given $query.
     */
    public function findByQuery(QueryInterface $query): ResultIteratorInterface;

    /**
     * This method retrieves all documents matching the given $query and initializes a scroll cursor.
     */
    public function findScrollableByQuery(
        QueryInterface $query,
        string|null $scrollContextKeepalive = null
    ): ResultIteratorInterface;

    /**
     * This method retrieves all documents available for an existing scroll cursor, identified by $scrollId.
     *
     * Use RepositoryInterface::findScrollableByQuery() to initialize the scroll cursor.
     */
    public function findByScrollId(
        string $scrollId,
        string|null $scrollContextKeepalive = null
    ): ResultIteratorInterface;

    /**
     * This method clears the $scrollId
     */
    public function clearScrollId(string $scrollId): void;

    /**
     * This method retrieves a single document based on a given $id.
     */
    public function findById(string $id, array $sourceFields = []): object|array|null;

    /**
     * This method bulk retrieves multiple documents based on the given $ids.
     */
    public function findByIds(array $ids, array $sourceFields = []): array;

    /**
     * This method returns the total document count in an index.
     */
    public function count(): int;

    /**
     * This method returns the total number of documents matching a given $query.
     */
    public function countByQuery(QueryInterface $query): int;

    /**
     * This method executes aggregations specified in $query and retrieves their results as well as the matching documents.
     */
    public function aggregateByQuery(QueryInterface $query): AggregationResultSetInterface;

    /**
     * This method executes a query with composite aggregation, iterates through the results, and retrieves the data.
     *
     * @return Generator <CompositeResult>
     */
    public function aggregateCompositeByQuery(CompositeAggregationQueryInterface $query): Generator;

    /**
     * This method updates all documents matching a given $query using a given $updateScript.
     */
    public function updateByQuery(QueryInterface $query, array $updateScript): array;
}
