<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Query\ElasticaQuery;
use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Query\SortDirection;
use Elastica\Exception\InvalidException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticaQueryTest extends MockeryTestCase
{
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
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     */
    public function testCreateEmpty(QueryInterface $query): void
    {
        $queryAsArray = $query->toArray();

        $this->assertArrayHasKey('query', $queryAsArray);
        $this->assertArrayHasKey('match_all', $queryAsArray['query']);
    }

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

    public function testCreateInvalid(): void
    {
        $this->expectException(InvalidException::class);

        ElasticaQuery::create(42);
    }

    public function testSkip(): void
    {
        $query = ElasticaQuery::create()->skip(10);

        $this->assertEquals(10, $query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals([], $query->getSort());
    }

    public function testLimit(): void
    {
        $query = ElasticaQuery::create()->limit(10);

        $this->assertEquals(10, $query->getLimit());
        $this->assertNull($query->getOffset());
        $this->assertEquals([], $query->getSort());
    }

    public function sortData(): array
    {
        return [
            'one field' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'direction' => SortDirection::ASC,
                    ],
                ],
                'expected_output' => [
                    ['foo' => SortDirection::ASC],
                ],
            ],
            'two fields' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'direction' => SortDirection::ASC,
                    ],
                    [
                        'key' => 'bar',
                        'direction' => SortDirection::DESC,
                    ],
                ],
                'expected_output' => [
                    ['foo' => SortDirection::ASC],
                    ['bar' => SortDirection::DESC],
                ],
            ],
            'override one field (works unexpected in elastica)' => [
                'input' => [
                    [
                        'key' => 'foo',
                        'direction' => SortDirection::ASC,
                    ],
                    [
                        'key' => 'foo',
                        'direction' => SortDirection::DESC,
                    ],
                ],
                'expected_output' => [
                    ['foo' => SortDirection::ASC],
                    ['foo' => SortDirection::DESC],
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
            $query->sort($command['key'], $command['direction']);
        }

        $this->assertNull($query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals($expectedOutput, $query->getSort());
    }

    public function testSortInvalidDirection(): void
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
        $serialized = $query->toArray();
        $this->assertArrayHasKey('_source', $serialized);
        $this->assertEquals($expectedOutput, $serialized['_source']);
    }
}
