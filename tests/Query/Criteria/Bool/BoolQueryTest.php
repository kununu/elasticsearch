<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Bool;

use Kununu\Elasticsearch\Query\Criteria\Bool\AbstractBoolQuery;
use Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('createDataProvider')]
    public function testCreate(array $input): void
    {
        self::assertEquals($input, $this->getConcreteBoolQuery($input)->getChildren());
    }

    public static function createDataProvider(): array
    {
        return [
            'empty'                   => [
                'input' => [],
            ],
            'with_a_filter'           => [
                'input' => [Filter::create('field', 'value')],
            ],
            'with_two_filters_search' => [
                'input' => [
                    Filter::create('field', 'value'),
                    Filter::create('another_field', 'another_value'),
                ],
            ],
        ];
    }

    #[DataProvider('createDataProvider')]
    public function testAdd(array $input): void
    {
        $boolQuery = $this->getConcreteBoolQuery([]);

        self::assertEquals([], $boolQuery->getChildren());

        foreach ($input as $child) {
            $boolQuery->add($child);
        }

        self::assertEquals($input, $boolQuery->getChildren());
    }

    #[DataProvider('createDataProvider')]
    public function testToArray(array $input): void
    {
        $boolQuery = $this->getConcreteBoolQuery($input);

        self::assertEquals($input, $boolQuery->getChildren());

        $serialized = $boolQuery->toArray();

        self::assertArrayHasKey('bool', $serialized);
        self::assertArrayHasKey('my_operator', $serialized['bool']);
        self::assertCount(count($input), $serialized['bool']['my_operator']);
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
