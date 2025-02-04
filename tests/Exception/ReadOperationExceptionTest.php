<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ReadOperationExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new ReadOperationException(
            'Error message',
            $previous = new Exception(),
            $query = new stdClass(),
            'PREFIX: '
        );

        self::assertEquals('PREFIX: Error message', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame($query, $exception->getQuery());
    }
}
