<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\NoFieldsException;
use PHPUnit\Framework\TestCase;

final class NoFieldsExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new NoFieldsException();

        self::assertEquals('No fields given', $exception->getMessage());
    }
}
