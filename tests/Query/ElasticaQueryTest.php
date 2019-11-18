<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query;

use Elastica\Exception\InvalidException;
use Kununu\Elasticsearch\Query\ElasticaQuery;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Query\SortOrder;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticaQueryTest extends MockeryTestCase
{
    /**
     * @return array
     */
    public function createEmptyData(): array
    {
        return [
            'implicit null' => [
                'query' => ElasticaQuery::create(),
            ],
            'explicit null' => [
                'query' => ElasticaQuery::create(null),
            ],
            'empty int' => [
                'query' => ElasticaQuery::create(0),
            ],
            'empty string' => [
                'query' => ElasticaQuery::create(''),
            ],
        ];
    }

    /**
     * @dataProvider createEmptyData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     */
    public function testCreateEmpty(QueryInterface $query): void
    {
        $queryAsArray = $query->toArray();

        $this->assertArrayHasKey('query', $queryAsArray);
        $this->assertArrayHasKey('match_all', $queryAsArray['query']);
    }

    /**
     * @return array
     */
    public function createQueryStringData(): array
    {
        return [
            'empty string' => [
                'query' => ElasticaQuery::create(''),
            ],
            'non-empty string' => [
                'query' => ElasticaQuery::create('some searchterm'),
            ],
        ];
    }

    public function testCreateQueryString(): void
    {
        $searchTerm = 'some searchterm';

        $this->assertEquals(
            [
                'query' => [
                    'query_string' => [
                        'query' => $searchTerm,
                    ],
                ],
            ],
            ElasticaQuery::create($searchTerm)->toArray()
        );
    }

    public function testCreateSelf(): void
    {
        $query = ElasticaQuery::create();
        $this->assertEquals($query, ElasticaQuery::create($query));
    }

    public function testCreateArray(): void
    {
        $searchTerm = 'some searchterm';

        $this->assertEquals(
            [
                'query' => [
                    'query_string' => [
                        'query' => $searchTerm,
                    ],
                ],
            ],
            ElasticaQuery::create(
                [
                    'query' => [
                        'query_string' => [
                            'query' => $searchTerm,
                        ],
                    ],
                ]
            )->toArray()
        );
    }

    public function testCreateInvalid(): void
    {
        $this->expectException(InvalidException::class);

        ElasticaQuery::create(42);
    }

    public function testSkip(): void
    {
        $query = ElasticaQuery::create();

        $this->assertNull($query->getOffset());

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
        $query = ElasticaQuery::create();

        $this->assertNull($query->getLimit());

        $query->limit(10);

        $this->assertEquals(10, $query->getLimit());
        $this->assertNull($query->getOffset());
        $this->assertEquals([], $query->getSort());

        // override limit
        $query->limit(20);

        $this->assertEquals(20, $query->getLimit());
    }

    /**
     * @return array
     */
    public function sortData(): array
    {
        return [
            'one field' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                ],
                'expected_output' => [
                    ['foo' => SortOrder::ASC],
                ],
            ],
            'two fields' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                    [
                        'key' => 'bar',
                        'order' => SortOrder::DESC,
                    ],
                ],
                'expected_output' => [
                    ['foo' => SortOrder::ASC],
                    ['bar' => SortOrder::DESC],
                ],
            ],
            'override one field (works unexpected in elastica)' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                    [
                        'key' => 'foo',
                        'order' => SortOrder::DESC,
                    ],
                ],
                'expected_output' => [
                    ['foo' => SortOrder::ASC],
                    ['foo' => SortOrder::DESC],
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
        $query = ElasticaQuery::create();
        foreach ($input as $command) {
            $query->sort($command['key'], $command['order']);
        }

        $this->assertNull($query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals($expectedOutput, $query->getSort());
    }

    public function testSortInvalidOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ElasticaQuery::create()->sort('foo', 'bar');
    }

    public function testElasticaExceptionHandling(): void
    {
        $query = ElasticaQuery::create();
        $this->assertNull($query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals([], $query->getSort());
    }

    /**
     * @return array
     */
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
        $query = ElasticaQuery::create();
        $query->select($input);
        $this->assertEquals($expectedOutput, $query->getParam('_source'));
        $this->assertEquals($expectedOutput, $query->getSelect());
        $serialized = $query->toArray();
        $this->assertArrayHasKey('_source', $serialized);
        $this->assertEquals($expectedOutput, $serialized['_source']);
    }
}
