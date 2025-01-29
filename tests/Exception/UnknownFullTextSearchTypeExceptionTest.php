<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\UnknownFullTextSearchTypeException;
use PHPUnit\Framework\TestCase;

final class UnknownFullTextSearchTypeExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UnknownFullTextSearchTypeException('TYPE');

        self::assertEquals('Unknown full text search type "TYPE" given', $exception->getMessage());
    }
}
