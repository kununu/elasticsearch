<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Exception;
use Kununu\Elasticsearch\Exception\UpsertException;
use PHPUnit\Framework\TestCase;

final class UpsertExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UpsertException(
            'Error message',
            $previous = new Exception(),
            $documentId = '23afd',
            $document = ['first' => 'bar', 'middle' => 'foo', 'age' => 21],
            'PREFIX: '
        );

        self::assertEquals('PREFIX: Error message', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertEquals($documentId, $exception->getDocumentId());
        self::assertEquals($document, $exception->getDocument());
    }
}
