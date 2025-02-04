<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Exception;
use Kununu\Elasticsearch\Exception\BulkException;
use PHPUnit\Framework\TestCase;

final class BulkExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new BulkException(
            'Error message',
            $previous = new Exception(),
            $operations = [1, 2, 3],
            'PREFIX: '
        );

        self::assertEquals('PREFIX: Error message', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertEquals($operations, $exception->getOperations());
    }
}
