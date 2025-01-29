<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\UnknownChildArgumentTypeException;
use PHPUnit\Framework\TestCase;

final class UnknownChildArgumentTypeExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UnknownChildArgumentTypeException(5);

        self::assertEquals('Argument #5 is of unknown type', $exception->getMessage());
    }
}
