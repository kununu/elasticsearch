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
        $sources = new Sources();
        self::assertCount(0, $sources);

        $sources = new Sources(new SourceProperty('source', 'property', true));
        $sources->append(new SourceProperty('source2', 'property2', false));
        self::assertCount(2, $sources);
    }

    public function testSourcesWithInvalidSourceProperty(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Can only append Kununu\Elasticsearch\Query\Aggregation\SourceProperty');

        (new Sources())->append('invalid');
    }
}
