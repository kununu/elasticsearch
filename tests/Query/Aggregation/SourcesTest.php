<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Aggregation;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Aggregation\SourceProperty;
use Kununu\Elasticsearch\Query\Aggregation\Sources;
use PHPUnit\Framework\TestCase;

final class SourcesTest extends TestCase
{
    public function testSources(): void
    {
        $sources = (new Sources(new SourceProperty('source', 'property', true)))
            ->add(new SourceProperty('source2', 'property2', false));

        self::assertCount(2, $sources);
        self::assertInstanceOf(SourceProperty::class, $sources->current());

        $sources = new Sources();

        self::assertEmpty($sources);
        self::assertNull($sources->current());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Can only append %s', SourceProperty::class));

        $sources->append('Invalid');
    }
}
