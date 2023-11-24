<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\Bool\Should;
use PHPUnit\Framework\TestCase;

final class ShouldTest extends TestCase
{
    public function testOperator(): void
    {
        $this->assertEquals('should', Should::OPERATOR);
    }
}
