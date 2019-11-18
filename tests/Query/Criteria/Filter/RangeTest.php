<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Filter;

use App\Services\Elasticsearch\Query\Criteria\Filter\Range;
use App\Services\Elasticsearch\Query\Criteria\Operator;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class RangeTest extends MockeryTestCase
{
    public function testOneElementWithoutOptions(): void
    {
        $this->assertEquals(
            [
                'range' => [
                    'field_a' => [
                        'gte' => 7,
                    ],
                ],
            ],
            Range::asArray('field_a', [Operator::GREATER_THAN_EQUALS => 7])
        );
    }

    public function testMultipleElementsWithoutOptions(): void
    {
        $this->assertEquals(
            [
                'range' => [
                    'field_a' => [
                        'gte' => 7,
                        'lt' => 42,
                    ],
                ],
            ],
            Range::asArray('field_a', [Operator::GREATER_THAN_EQUALS => 7, Operator::LESS_THAN => 42])
        );
    }

    public function testOneElementWithOptions(): void
    {
        $this->assertEquals(
            [
                'range' => [
                    'field_a' => [
                        'gte' => 7,
                        'boost' => 10,
                    ],
                ],
            ],
            Range::asArray('field_a', [Operator::GREATER_THAN_EQUALS => 7], ['boost' => 10])
        );
    }

    public function testMultipleElementsWithOptions(): void
    {
        $this->assertEquals(
            [
                'range' => [
                    'field_a' => [
                        'gte' => 7,
                        'lt' => 42,
                        'boost' => 10,
                    ],
                ],
            ],
            Range::asArray('field_a', [Operator::GREATER_THAN_EQUALS => 7, Operator::LESS_THAN => 42], ['boost' => 10])
        );
    }
}
