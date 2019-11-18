<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\Terms;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class TermsTest extends MockeryTestCase
{
    public function testWithoutOptions(): void
    {
        $this->assertEquals(
            [
                'terms' => [
                    'field_a' => ['foo', 'bar'],
                ],
            ],
            Terms::asArray('field_a', ['foo', 'bar'])
        );
    }

    public function testWithOptions(): void
    {
        $this->assertEquals(
            [
                'terms' => [
                    'field_a' => ['foo', 'bar'],
                    'boost' => 7,
                ],
            ],
            Terms::asArray('field_a', ['foo', 'bar'], ['boost' => 7])
        );
    }
}
