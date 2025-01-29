<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\UnhandledFullTextSearchTypeException;
use PHPUnit\Framework\TestCase;

final class UnhandledFullTextSearchTypeExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UnhandledFullTextSearchTypeException('TYPE');

        self::assertEquals(
            'Unhandled full text search type "TYPE". Please add an appropriate switch case.',
            $exception->getMessage()
        );
    }
}
