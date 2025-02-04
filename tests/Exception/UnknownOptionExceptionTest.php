<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\UnknownOptionException;
use PHPUnit\Framework\TestCase;

final class UnknownOptionExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new UnknownOptionException('OPTION');

        self::assertEquals('Unknown option "OPTION" given', $exception->getMessage());
    }
}
