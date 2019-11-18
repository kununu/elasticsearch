<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\Term;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class TermTest extends MockeryTestCase
{
    public function testWithoutOptions(): void
    {
        $this->assertEquals(
            [
                'term' => [
                    'field_a' => 'foo',
                ],
            ],
            Term::asArray('field_a', 'foo')
        );
    }

    public function testWithOptions(): void
    {
        $this->assertEquals(
            [
                'term' => [
                    'field_a' => [
                        'value' => 'foo',
                        'boost' => 7,
                    ],
                ],
            ],
            Term::asArray('field_a', 'foo', ['boost' => 7])
        );
    }
}
