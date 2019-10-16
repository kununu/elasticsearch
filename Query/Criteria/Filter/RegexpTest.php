<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Filter;

use App\Services\Elasticsearch\Query\Criteria\Filter\Regexp;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class RegexpTest extends MockeryTestCase
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
