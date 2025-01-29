<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Exception;

use Kununu\Elasticsearch\Exception\InvalidSearchArgumentException;
use PHPUnit\Framework\TestCase;

final class InvalidSearchArgumentExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new InvalidSearchArgumentException('Interface1', 'Interface2');

        self::assertEquals('Argument $search must be one of [Interface1, Interface2]', $exception->getMessage());
    }
}
