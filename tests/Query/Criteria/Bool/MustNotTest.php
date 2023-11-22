<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\Bool\MustNot;
use PHPUnit\Framework\TestCase;

final class MustNotTest extends TestCase
{
    public function testOperator(): void
    {
        $this->assertEquals('must_not', MustNot::OPERATOR);
    }
}
