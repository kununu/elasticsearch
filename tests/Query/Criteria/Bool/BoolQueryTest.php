<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\Bool\AbstractBoolQuery;
use Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use LogicException;
use PHPUnit\Framework\TestCase;

final class BoolQueryTest extends TestCase
{
    public function testInvalidOperator(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No operator defined');

        $myBoolFilter = new class() extends AbstractBoolQuery {
            public function getOperator(): string
            {
                return parent::getOperator();
            }
        };

        $myBoolFilter->getOperator();
    }

    /** @dataProvider createDataProvider */
    public function testCreate(array $input): void
    {
        $this->assertEquals($input, $this->getConcreteBoolQuery($input)->getChildren());
    }

    public static function createDataProvider(): array
    {
        return [
            'empty'                   => [
                'input' => [],
            ],
            'with a filter'           => [
                'input' => [Filter::create('field', 'value')],
            ],
            'with two filters search' => [
                'input' => [
                    Filter::create('field', 'value'),
                    Filter::create('another_field', 'another_value'),
                ],
            ],
        ];
    }

    /** @dataProvider createDataProvider */
    public function testAdd(array $input): void
    {
        $boolQuery = $this->getConcreteBoolQuery([]);

        $this->assertEquals([], $boolQuery->getChildren());

        foreach ($input as $child) {
            $boolQuery->add($child);
        }

        $this->assertEquals($input, $boolQuery->getChildren());
    }

    /** @dataProvider createDataProvider */
    public function testToArray(array $input): void
    {
        $boolQuery = $this->getConcreteBoolQuery($input);

        $this->assertEquals($input, $boolQuery->getChildren());

        $serialized = $boolQuery->toArray();

        $this->assertArrayHasKey('bool', $serialized);
        $this->assertArrayHasKey('my_operator', $serialized['bool']);
        $this->assertCount(count($input), $serialized['bool']['my_operator']);
    }

    private function getConcreteBoolQuery(array $input): BoolQueryInterface
    {
        return new class(...$input) extends AbstractBoolQuery {
            public const OPERATOR = 'my_operator';

            public function getChildren(): array
            {
                return $this->children;
            }
        };
    }
}
