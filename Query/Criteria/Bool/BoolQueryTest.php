<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Bool;

use App\Services\Elasticsearch\Exception\QueryException;
use App\Services\Elasticsearch\Query\Criteria\Bool\AbstractBoolQuery;
use App\Services\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface;
use App\Services\Elasticsearch\Query\Criteria\Filter;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class BoolQueryTest extends MockeryTestCase
{
    public function testInvalidOperator(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('No operator defined');

        $myBoolFilter = new class extends AbstractBoolQuery
        {
            public function getOperator(): string
            {
                return parent::getOperator();
            }
        };

        $myBoolFilter->getOperator();
    }

    /**
     * @return array
     */
    public function createData(): array
    {
        return [
            'empty' => [
                'input' => [],
            ],
            'with a filter' => [
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

    protected function getConcreteBoolQuery(array $input): BoolQueryInterface
    {
        return new class(...$input) extends AbstractBoolQuery
        {
            public const OPERATOR = 'my_operator';

            /**
             * @return array
             */
            public function getChildren(): array
            {
                return $this->children;
            }
        };
    }

    /**
     * @dataProvider createData
     *
     * @param array $input
     */
    public function testCreate(array $input): void
    {
        $this->assertEquals($input, $this->getConcreteBoolQuery($input)->getChildren());
    }

    /**
     * @dataProvider createData
     *
     * @param array $input
     */
    public function testAdd(array $input): void
    {
        $boolQuery = $this->getConcreteBoolQuery([]);

        $this->assertEquals([], $boolQuery->getChildren());

        foreach ($input as $child) {
            $boolQuery->add($child);
        }

        $this->assertEquals($input, $boolQuery->getChildren());
    }

    /**
     * @dataProvider createData
     *
     * @param array $input
     */
    public function testToArray(array $input): void
    {
        $boolQuery = $this->getConcreteBoolQuery($input);

        $this->assertEquals($input, $boolQuery->getChildren());

        $serialized = $boolQuery->toArray();

        $this->assertArrayHasKey('bool', $serialized);
        $this->assertArrayHasKey('my_operator', $serialized['bool']);
        $this->assertCount(count($input), $serialized['bool']['my_operator']);
    }
}
