<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Aggregation;

use Kununu\Collection\TestCase\AbstractCollectionTestCase;
use Kununu\Elasticsearch\Query\Aggregation\SourceProperty;
use Kununu\Elasticsearch\Query\Aggregation\Sources;

final class SourcesTest extends AbstractCollectionTestCase
{
    protected const int EXPECTED_COUNT = 2;
    protected const string EXPECTED_ITEM_CLASS = SourceProperty::class;
    protected const bool TEST_TO_ARRAY = false;

    protected function createCollection(): Sources
    {
        return new Sources(new SourceProperty('source', 'property', true))
            ->add(new SourceProperty('source2', 'property2', false));
    }

    protected function createEmptyCollection(): Sources
    {
        return new Sources();
    }
}
