<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\Bool\Should;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ShouldTest extends MockeryTestCase
{
    public function testOperator(): void
    {
        $this->assertEquals('should', Should::OPERATOR);
    }
}
