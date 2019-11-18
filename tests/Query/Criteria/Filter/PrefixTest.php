<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Filter;

use App\Services\Elasticsearch\Query\Criteria\Filter\Prefix;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class PrefixTest extends MockeryTestCase
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
                    'boost' => 7,
                ],
            ],
            Prefix::asArray('field_a', 'foo', ['boost' => 7])
        );
    }
}
