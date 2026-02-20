<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria;

use Kununu\Collection\TestCase\AbstractCollectionTestCase;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\Filters;

final class FiltersTest extends AbstractCollectionTestCase
{
    protected const int EXPECTED_COUNT = 2;
    protected const string EXPECTED_ITEM_CLASS = Filter::class;
    protected const bool TEST_TO_ARRAY = false;

    protected function createCollection(): Filters
    {
        return new Filters(new Filter('field', 'value'))
            ->add(new Filter('field2', 'value2'));
    }

    protected function createEmptyCollection(): Filters
    {
        return new Filters();
    }
}
