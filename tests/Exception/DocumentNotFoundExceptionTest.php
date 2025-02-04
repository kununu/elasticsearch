<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Exception;
use Kununu\Elasticsearch\Exception\DocumentNotFoundException;
use PHPUnit\Framework\TestCase;

final class DocumentNotFoundExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new DocumentNotFoundException(
            $documentId = 'aa4e3f33-b890-47b9-bcd4-d7d80b442339',
            $previous = new Exception(),
            'PREFIX: '
        );

        self::assertEquals(sprintf('PREFIX: No document found with id %s', $documentId), $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertEquals($documentId, $exception->getDocumentId());
    }
}
