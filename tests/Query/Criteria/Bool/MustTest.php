<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\Bool\Must;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class MustTest extends MockeryTestCase
{
    public function testOperator(): void
    {
        $this->assertEquals('must', Must::OPERATOR);
    }
}
