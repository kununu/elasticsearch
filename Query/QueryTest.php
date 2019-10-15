<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Query\Aggregation;
use App\Services\Elasticsearch\Query\Aggregation\Metric;
use App\Services\Elasticsearch\Query\Criteria\Bool\Must;
use App\Services\Elasticsearch\Query\Criteria\Bool\MustNot;
use App\Services\Elasticsearch\Query\Criteria\Bool\Should;
use App\Services\Elasticsearch\Query\Criteria\Filter;
use App\Services\Elasticsearch\Query\Criteria\Search;
use App\Services\Elasticsearch\Query\Query;
use App\Services\Elasticsearch\Query\SortOrder;
use InvalidArgumentException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class QueryTest extends MockeryTestCase
{
    protected const FIELD_NAME_SEARCHES = 'searches';
    protected const FIELD_NAME_FILTERS = 'filters';
    protected const FIELD_NAME_AGGREGATIONS = 'aggregations';

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
            'with a search' => [
                'input' => [Search::create(['field'], 'value')],
            ],
            'with an aggregation' => [
                'input' => [Aggregation::create('field', Metric::SUM)],
            ],
            'with a little bit of everything' => [
                'input' => [
                    Filter::create('field', 'value'),
                    Search::create(['field'], 'value'),
                    Aggregation::create('field', Metric::SUM),
                ],
            ],
        ];
    }

    /**
     * @param \App\Services\Elasticsearch\Query\Query $query
     * @param string                                  $fieldName
     *
     * @return \ReflectionProperty
     */
    protected function getPublicReflectionProperty(Query $query, string $fieldName): \ReflectionProperty
    {
        $property = new \ReflectionProperty(Query::class, $fieldName);
        $property->setAccessible(true);

        return $property;
    }

    /**
     * @param \App\Services\Elasticsearch\Query\Query $query
     * @param string                                  $fieldName
     * @param array                                   $expected
     */
    protected function assertChildren(Query $query, string $fieldName, array $expected): void
    {
        $this->assertEquals($expected, $this->getPublicReflectionProperty($query, $fieldName)->getValue($query));
    }

    /**
     * @dataProvider createData
     *
     * @param array $input
     */
    public function testCreate(array $input): void
    {
        $children = [
            Search::class => [],
            Filter::class => [],
            Aggregation::class => [],
        ];

        foreach ($input as $child) {
            $children[get_class($child)][] = $child;
        }

        $query = Query::create(...$input);

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);
    }

    /**
     * @dataProvider createData
     *
     * @param array $input
     */
    public function testAdd(array $input): void
    {
        $children = [
            Search::class => [],
            Filter::class => [],
            Aggregation::class => [],
        ];

        $query = Query::create();

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);

        foreach ($input as $child) {
            $children[get_class($child)][] = $child;
            $query->add($child);
        }

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);
    }

    public function testCreateWithOnlyInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument #0 is of unknown type');

        Query::create('foo');
    }

    public function testCreateWithValidAndInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument #1 is of unknown type');

        Query::create(Filter::create('field', 'value'), 'foo');
    }

    public function testAddInvalid(): void
    {
        $query = Query::create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument #0 is of unknown type');

        $query->add('foo');
    }

    public function testSearch(): void
    {
        $query = Query::create();

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, []);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);

        $search = Search::create(['field'], 'value');
        $query->search($search);

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, [$search]);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, []);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);
    }

    public function testWhere(): void
    {
        $query = Query::create();

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, []);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);

        $filter = Filter::create('field', 'value');
        $query->where($filter);

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, [$filter]);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);
    }

    public function testAggregate(): void
    {
        $query = Query::create();

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, []);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);

        $aggregation = Aggregation::create('field', Metric::SUM);
        $query->aggregate($aggregation);

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, []);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, [$aggregation]);
    }

    public function testMinScore(): void
    {
        $query = Query::create();

        $this->assertNull($query->getMinScore());

        $query->setMinScore(42);

        $this->assertEquals(42, $query->getMinScore());
    }

    public function testSearchOperator(): void
    {
        $query = Query::create();

        $this->assertEquals(Should::OPERATOR, $query->getSearchOperator());

        $query->setSearchOperator(Must::OPERATOR);

        $this->assertEquals(Must::OPERATOR, $query->getSearchOperator());
    }

    public function testSetInvalidSearchOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value \'must_not\' is not valid.');

        Query::create()
            ->setSearchOperator(MustNot::OPERATOR);
    }

    public function testCommonFunctionalityIsPreservedOnToArray(): void
    {
        $query = Query::create(Filter::create('field', 'value'))
            ->select(['field_a'])
            ->sort('field_a')
            ->skip(1)
            ->limit(10);

        $this->assertEquals(
            [
                'query' => ['bool' => ['filter' => ['bool' => ['must' => [['term' => ['field' => 'value']]]]]]],
                '_source' => ['field_a'],
                'size' => 10,
                'from' => 1,
                'sort' => [
                    'field_a' => ['order' => SortOrder::ASC],
                ],
            ],
            $query->toArray()
        );
    }

    /**
     * @return array
     */
    public function toArrayData(): array
    {
        return [
            'empty' => [
                'query' => Query::create(),
                'expected' => [],
            ],
            'with a min_score' => [
                'query' => Query::create()->setMinScore(42),
                'expected' => [
                    'min_score' => 42,
                ],
            ],
            'with a filter' => [
                'query' => Query::create(Filter::create('field', 'value')),
                'expected' => [
                    'query' => ['bool' => ['filter' => ['bool' => ['must' => [['term' => ['field' => 'value']]]]]]],
                ],
            ],
            'with a search, default search operator' => [
                'query' => Query::create(Search::create(['field_a'], 'foo')),
                'expected' => [
                    'query' => [
                        'bool' => [
                            'should' => [['query_string' => ['fields' => ['field_a'], 'query' => 'foo']]],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ],
            'with two searches, AND connected' => [
                'query' => Query::create(
                    Search::create(['field_a'], 'foo'),
                    Search::create(['field_b'], 'bar')
                )->setSearchOperator(Must::OPERATOR),
                'expected' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['fields' => ['field_a'], 'query' => 'foo']],
                                ['query_string' => ['fields' => ['field_b'], 'query' => 'bar']],
                            ],
                        ],
                    ],
                ],
            ],
            'with an aggregation' => [
                'query' => Query::create(Aggregation::create('field_a', Metric::SUM, 'my_agg')),
                'expected' => [
                    'aggs' => [
                        'my_agg' => [
                            'sum' => ['field' => 'field_a'],
                        ],
                    ],
                ],
            ],
            'with a little bit of everything' => [
                'query' => Query::create(
                    Filter::create('field', 'value'),
                    Search::create(['field_a'], 'foo'),
                    Aggregation::create('field_a', Metric::SUM, 'my_agg')
                )->setMinScore(42),
                'expected' => [
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['query_string' => ['fields' => ['field_a'], 'query' => 'foo']],
                            ],
                            'filter' => ['bool' => ['must' => [['term' => ['field' => 'value']]]]],
                            'minimum_should_match' => 1,
                        ],
                    ],
                    'aggs' => [
                        'my_agg' => [
                            'sum' => ['field' => 'field_a'],
                        ],
                    ],
                    'min_score' => 42,
                ],
            ],
        ];
    }

    /**
     * @dataProvider toArrayData
     *
     * @param \App\Services\Elasticsearch\Query\Query $query
     * @param array                                   $expected
     */
    public function testToArray(Query $query, array $expected): void
    {
        $this->assertEquals($expected, $query->toArray());
    }
}
