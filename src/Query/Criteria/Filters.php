<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

use InvalidArgumentException;
use Kununu\Collection\AbstractCollection;

class Filters extends AbstractCollection
{
    private const INVALID = 'Can only append %s';

    public function __construct(Filter ...$propertyFilters)
    {
        parent::__construct();

        foreach ($propertyFilters as $propertyFilter) {
            $this->append($propertyFilter);
        }
    }

    public function current(): ?Filter
    {
        $current = parent::current();
        assert($this->count() > 0 ? $current instanceof Filter : null === $current);

        return $current;
    }

    public function append($value): void
    {
        match (true) {
            $value instanceof Filter  => parent::append($value),
            default                           => throw new InvalidArgumentException(sprintf(self::INVALID, Filter::class))
        };
    }
}
