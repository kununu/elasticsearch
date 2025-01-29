<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Exception;
use Kununu\Elasticsearch\Exception\DeleteException;
use PHPUnit\Framework\TestCase;

final class DeleteExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new DeleteException(
            'Error message',
            $previous = new Exception(),
            $documentId = 5000,
            'PREFIX: '
        );

        self::assertEquals('PREFIX: Error message', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertEquals($documentId, $exception->getDocumentId());
    }
}
