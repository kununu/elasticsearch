<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\UnknownAggregationTypeException;
use PHPUnit\Framework\TestCase;

final class UnknownAggregationTypeExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UnknownAggregationTypeException('TYPE');

        self::assertEquals('Unknown type "TYPE" given', $exception->getMessage());
    }
}
