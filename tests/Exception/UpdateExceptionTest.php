<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Exception;
use Kununu\Elasticsearch\Exception\UpdateException;
use PHPUnit\Framework\TestCase;

final class UpdateExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UpdateException(
            'Error message',
            $previous = new Exception(),
            $documentId = 1000,
            $document = ['name' => 'foo', 'surname' => 'bar'],
            'PREFIX: '
        );

        self::assertEquals('PREFIX: Error message', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertEquals($documentId, $exception->getDocumentId());
        self::assertEquals($document, $exception->getDocument());
    }
}
