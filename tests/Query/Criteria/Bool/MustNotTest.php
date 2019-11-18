<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\Bool\MustNot;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class MustNotTest extends MockeryTestCase
{
    public function testOperator(): void
    {
        $this->assertEquals('must_not', MustNot::OPERATOR);
    }
}
