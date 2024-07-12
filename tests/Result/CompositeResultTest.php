<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Result;

use Kununu\Elasticsearch\Result\CompositeResult;
use PHPUnit\Framework\TestCase;

final class CompositeResultTest extends TestCase
{
    public function testCompositeResult(): void
    {
        $compositeResult = new CompositeResult(['key' => 'value'], 1, 'agg');

        self::assertEquals(['key' => 'value'], $compositeResult->results);
        self::assertEquals(1, $compositeResult->documentsCount);
        self::assertEquals('agg', $compositeResult->aggregationName);
    }
}
