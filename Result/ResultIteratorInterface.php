<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Result;

/**
 * Interface ResultIteratorInterface
 *
 * @package App\Services\Elasticsearch\Result
 */
interface ResultIteratorInterface
{
    /**
     * @param array $results
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public static function create(array $results = []): ResultIteratorInterface;

    /**
     * @param int $total
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function setTotal(int $total): ResultIteratorInterface;

    /**
     * @return int
     */
    public function getTotal(): int;

    /**
     * @return int
     */
    public function getCount(): int;

    /**
     * @return string|null
     */
    public function getScrollId(): ?string;

    /**
     * @param string|null $scrollId
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function setScrollId(?string $scrollId): ResultIteratorInterface;

    /**
     * @return array
     */
    public function asArray(): array;

    /**
     * @param array $result
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function push(array $result): ResultIteratorInterface;

    /**
     * Returns the first result in this iterator for which the given callable returns a true-ish value.
     *
     * @param callable $fn (result)
     *
     * @return array|null
     */
    public function first(callable $fn): ?array;

    /**
     * Returns true, if for at least one result in this iterator the given callable returns a true-ish value.
     *
     * @param callable $fn (result, key)
     *
     * @return bool
     */
    public function some(callable $fn): bool;

    /**
     * Returns true, if for all results in this iterator the given callable returns a true-ish value.
     *
     * @param callable $fn (result, key)
     *
     * @return bool
     */
    public function every(callable $fn): bool;

    /**
     * Calls given callable on every result in this iterator.
     *
     * @param callable $fn (result, key)
     */
    public function each(callable $fn): void;

    /**
     * Calls given callable on every result in this iterator and returns an array of the return values of the callable.
     *
     * @param callable $fn (result, key)
     *
     * @return array
     */
    public function map(callable $fn): array;

    /**
     * @param callable $fn (carry, result, key)
     * @param mixed    $initial
     *
     * @return mixed
     */
    public function reduce(callable $fn, $initial = null);
}
