<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\Prefix;
use PHPUnit\Framework\TestCase;

final class PrefixTest extends TestCase
{
    public function testWithoutOptions(): void
    {
        $this->assertEquals(
            [
                'prefix' => [
                    'field_a' => 'foo',
                ],
            ],
            Prefix::asArray('field_a', 'foo')
        );
    }

    public function testWithOptions(): void
    {
        $this->assertEquals(
            [
                'prefix' => [
                    'field_a' => 'foo',
                    'boost'   => 7,
                ],
            ],
            Prefix::asArray('field_a', 'foo', ['boost' => 7])
        );
    }
}
