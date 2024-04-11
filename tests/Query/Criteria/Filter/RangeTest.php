<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\Range;
use Kununu\Elasticsearch\Query\Criteria\Operator;
use PHPUnit\Framework\TestCase;

final class RangeTest extends TestCase
{
    public function testOneElementWithoutOptions(): void
    {
        self::assertEquals(
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
        self::assertEquals(
            [
                'range' => [
                    'field_a' => [
                        'gte' => 7,
                        'lt'  => 42,
                    ],
                ],
            ],
            Range::asArray('field_a', [Operator::GREATER_THAN_EQUALS => 7, Operator::LESS_THAN => 42])
        );
    }

    public function testOneElementWithOptions(): void
    {
        self::assertEquals(
            [
                'range' => [
                    'field_a' => [
                        'gte'   => 7,
                        'boost' => 10,
                    ],
                ],
            ],
            Range::asArray('field_a', [Operator::GREATER_THAN_EQUALS => 7], ['boost' => 10])
        );
    }

    public function testMultipleElementsWithOptions(): void
    {
        self::assertEquals(
            [
                'range' => [
                    'field_a' => [
                        'gte'   => 7,
                        'lt'    => 42,
                        'boost' => 10,
                    ],
                ],
            ],
            Range::asArray('field_a', [Operator::GREATER_THAN_EQUALS => 7, Operator::LESS_THAN => 42], ['boost' => 10])
        );
    }
}
