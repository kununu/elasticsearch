<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Exception;
use Kununu\Elasticsearch\Exception\RepositoryException;
use PHPUnit\Framework\TestCase;

final class RepositoryExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new RepositoryException(
            'Error message',
            $previous = new Exception(),
            'PREFIX: '
        );

        self::assertEquals('PREFIX: Error message', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}
