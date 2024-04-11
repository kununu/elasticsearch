<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\Bool\Must;
use PHPUnit\Framework\TestCase;

final class MustTest extends TestCase
{
    public function testOperator(): void
    {
        self::assertEquals('must', Must::OPERATOR);
    }
}
