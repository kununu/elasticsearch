<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\Regexp;
use PHPUnit\Framework\TestCase;

final class RegexpTest extends TestCase
{
    public function testWithoutOptions(): void
    {
        $this->assertEquals(
            [
                'regexp' => [
                    'field_a' => 'foo',
                ],
            ],
            Regexp::asArray('field_a', 'foo')
        );
    }

    public function testWithOptions(): void
    {
        $this->assertEquals(
            [
                'regexp' => [
                    'field_a' => [
                        'value' => 'foo',
                        'boost' => 7,
                    ],
                ],
            ],
            Regexp::asArray('field_a', 'foo', ['boost' => 7])
        );
    }
}
