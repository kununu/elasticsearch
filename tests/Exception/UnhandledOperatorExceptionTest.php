<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\UnhandledOperatorException;
use PHPUnit\Framework\TestCase;

final class UnhandledOperatorExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UnhandledOperatorException('OPERATOR');

        self::assertEquals('Unhandled operator "OPERATOR"', $exception->getMessage());
    }
}
