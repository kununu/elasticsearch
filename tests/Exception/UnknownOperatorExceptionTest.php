<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\UnknownOperatorException;
use PHPUnit\Framework\TestCase;

final class UnknownOperatorExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UnknownOperatorException('OPERATOR');

        self::assertEquals('Unknown operator "OPERATOR" given', $exception->getMessage());
    }
}
