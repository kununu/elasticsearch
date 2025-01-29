<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\NoOperatorDefinedException;
use PHPUnit\Framework\TestCase;

final class NoOperatorDefinedExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new NoOperatorDefinedException();

        self::assertEquals('No operator defined', $exception->getMessage());
    }
}
