<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Aggregation;

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
final class Sources extends AbstractCollection
{
    private const string INVALID = 'Can only append %s';

    public function __construct(SourceProperty ...$sourceProperties)
    {
        parent::__construct();

        foreach ($sourceProperties as $sourceProperty) {
            $this->append($sourceProperty);
        }
    }

    public function current(): ?SourceProperty
    {
        $current = parent::current();
        assert($this->count() > 0 ? $current instanceof SourceProperty : null === $current);

        return $current;
    }

    public function append($value): void
    {
        match (true) {
            $value instanceof SourceProperty => parent::append($value),
            default                          => throw new InvalidArgumentException(
                sprintf(self::INVALID, SourceProperty::class)
            ),
        };
    }
}
