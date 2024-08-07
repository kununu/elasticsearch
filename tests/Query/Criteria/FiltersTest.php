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
        $filters = new Filters();
        self::assertCount(0, $filters);

        $filters = new Filters(new Filter('field', 'value'));
        $filters->append(new Filter('field2', 'value2'));
        self::assertCount(2, $filters);
    }

    public function testFiltersWithInvalidFilter(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Can only append Kununu\Elasticsearch\Query\Criteria\Filter');

        (new Filters())->append('invalid');
    }
}
