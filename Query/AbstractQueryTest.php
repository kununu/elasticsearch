<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Query\AbstractQuery;
use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Query\SortOrder;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class AbstractQueryTest extends MockeryTestCase
{
    /**
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    protected function getQuery(): QueryInterface
    {
        return new class extends AbstractQuery
        {
            /**
             * @return array
             */
            public function toArray(): array
            {
                return $this->buildBaseBody();
            }
        };
    }

    public function testBuildBaseBodyEmpty(): void
    {
        $query = $this->getQuery();

        $this->assertEquals([], $query->toArray());
    }

    public function selectData(): array
    {
        return [
            'no field' => [
                'input' => [],
                'expected_output' => false,
            ],
            'one field' => [
                'input' => [
                    'foo',
                ],
                'expected_output' => [
                    'foo',
                ],
            ],
            'two fields' => [
                'input' => [
                    'foo',
                    'bar',
                ],
                'expected_output' => [
                    'foo',
                    'bar',
                ],
            ],
            'same field twice' => [
                'input' => [
                    'foo',
                    'foo',
                ],
                'expected_output' => [
                    'foo',
                ],
            ],
        ];
    }

    /**
     * @dataProvider selectData
     *
     * @param $input
     * @param $expectedOutput
     */
    public function testSelect($input, $expectedOutput): void
    {
        $query = $this->getQuery();

        $this->assertNull($query->getSelect());
        $this->assertEquals([], $query->toArray());

        $query->select($input);

        $this->assertEquals($input, $query->getSelect());
        $serialized = $query->toArray();
        $this->assertArrayHasKey('_source', $serialized);
        $this->assertEquals($expectedOutput, $serialized['_source']);
    }

    public function testSkip(): void
    {
        $query = $this->getQuery();

        $this->assertNull($query->getOffset());
        $this->assertEquals([], $query->toArray());

        $query->skip(10);

        $this->assertEquals(10, $query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals([], $query->getSort());

        // override offset
        $query->skip(20);

        $this->assertEquals(20, $query->getOffset());
    }

    public function testLimit(): void
    {
        $query = $this->getQuery();

        $this->assertNull($query->getLimit());
        $this->assertEquals([], $query->toArray());

        $query->limit(10);

        $this->assertEquals(10, $query->getLimit());
        $this->assertNull($query->getOffset());
        $this->assertEquals([], $query->getSort());

        // override limit
        $query->limit(20);

        $this->assertEquals(20, $query->getLimit());
    }

    public function sortData(): array
    {
        return [
            'one field' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'direction' => SortOrder::ASC,
                    ],
                ],
                'expected_output' => [
                    'foo' => ['order' => SortOrder::ASC],
                ],
            ],
            'two fields' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'direction' => SortOrder::ASC,
                    ],
                    [
                        'key' => 'bar',
                        'direction' => SortOrder::DESC,
                    ],
                ],
                'expected_output' => [
                    'foo' => ['order' => SortOrder::ASC],
                    'bar' => ['order' => SortOrder::DESC],
                ],
            ],
            'override one field' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'direction' => SortOrder::ASC,
                    ],
                    [
                        'key' => 'foo',
                        'direction' => SortOrder::DESC,
                    ],
                ],
                'expected_output' => [
                    'foo' => ['order' => SortOrder::DESC],
                ],
            ],
        ];
    }

    /**
     * @dataProvider sortData
     *
     * @param array $input
     * @param array $expectedOutput
     */
    public function testSort(array $input, array $expectedOutput): void
    {
        $query = $this->getQuery();

        $this->assertEquals([], $query->getSort());
        $this->assertEquals([], $query->toArray());

        foreach ($input as $command) {
            $query->sort($command['key'], $command['direction']);
        }

        $this->assertNull($query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals($expectedOutput, $query->getSort());
        $this->assertEquals(['sort' => $expectedOutput], $query->toArray());
    }

    public function testSortInvalidDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->getQuery()->sort('foo', 'bar');
    }
}
