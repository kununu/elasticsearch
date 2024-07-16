<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Aggregation;

use Kununu\Elasticsearch\Query\Aggregation\SourceProperty;
use PHPUnit\Framework\TestCase;

final class SourcePropertyTest extends TestCase
{
    public function testSourceProperty(): void
    {
        $sourceProperty = new SourceProperty('source', 'property', true);

        self::assertEquals('source', $sourceProperty->source);
        self::assertEquals('property', $sourceProperty->property);
        self::assertTrue($sourceProperty->missingBucket);
    }
}
