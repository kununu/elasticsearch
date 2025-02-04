<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\MissingAggregationAttributesException;
use PHPUnit\Framework\TestCase;

final class MissingAggregationAttributesExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new MissingAggregationAttributesException();

        self::assertEquals('Aggregation name is missing', $exception->getMessage());
    }
}
