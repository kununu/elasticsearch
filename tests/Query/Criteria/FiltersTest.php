<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\Filters;
use PHPUnit\Framework\TestCase;

final class FiltersTest extends TestCase
{
    public function testFilters(): void
    {
        $filters = (new Filters(new Filter('field', 'value')))
            ->add(new Filter('field2', 'value2'));

        self::assertCount(2, $filters);
        self::assertInstanceOf(Filter::class, $filters->current());

        $filters = new Filters();

        self::assertEmpty($filters);
        self::assertNull($filters->current());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Can only append %s', Filter::class));

        $filters->append('Invalid');
    }
}
