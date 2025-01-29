<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\OperationNotAcknowledgedException;
use PHPUnit\Framework\TestCase;

final class OperationNotAcknowledgedExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new OperationNotAcknowledgedException();

        self::assertEquals('Operation not acknowledged', $exception->getMessage());
    }
}
