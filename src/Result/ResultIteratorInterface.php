<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

interface ResultIteratorInterface
{
    public function setTotal(int $total): ResultIteratorInterface;

    public function getTotal(): int;

    public function getCount(): int;

    public function getScrollId(): ?string;

    public function setScrollId(?string $scrollId): ResultIteratorInterface;

    public function asArray(): array;

    public function push(array|object $result): ResultIteratorInterface;

    /**
     * Returns the first result in this iterator for which the given callable returns a true-ish value.
     *
     * @param callable $fn (result)
     */
    public function first(callable $fn): ?array;

    /**
     * Returns all results in this iterator for which the given callable returns a true-ish value.
     *
     * @param callable $fn (result)
     */
    public function filter(callable $fn): array;

    /**
     * Returns true, if for at least one result in this iterator the given callable returns a true-ish value.
     *
     * @param callable $fn (result, key)
     */
    public function some(callable $fn): bool;

    /**
     * Returns true, if for all results in this iterator the given callable returns a true-ish value.
     *
     * @param callable $fn (result, key)
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
     */
    public function map(callable $fn): array;

    /**
     * @param callable $fn (carry, result, key)
     */
    public function reduce(callable $fn, mixed $initial = null): mixed;
}
