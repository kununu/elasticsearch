<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

use InvalidArgumentException;
use Kununu\Collection\AbstractCollection;
use Kununu\Collection\Collection;

/**
 * @method static self fromIterable(iterable $data)
 * @method        self add(mixed $value)
 * @method        self diff(Collection $other)
 * @method        self duplicates(bool $strict = true, bool $uniques = false)
 * @method        self each(callable $function, bool $rewind = true)
 * @method        self reverse()
 * @method        self unique()
 */
final class Filters extends AbstractCollection
{
    private const string INVALID = 'Can only append %s';

    public function __construct(FilterInterface ...$filters)
    {
        parent::__construct();

        foreach ($filters as $filter) {
            $this->append($filter);
        }
    }

    public function current(): ?FilterInterface
    {
        $current = parent::current();
        assert($this->count() > 0 ? $current instanceof FilterInterface : null === $current);

        return $current;
    }

    public function append($value): void
    {
        match (true) {
            $value instanceof FilterInterface => parent::append($value),
            default                           => throw new InvalidArgumentException(
                sprintf(self::INVALID, FilterInterface::class)
            ),
        };
    }
}
