<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Aggregation;
use Kununu\Elasticsearch\Query\Aggregation\Metric;
use Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\Bool\Must;
use Kununu\Elasticsearch\Query\Criteria\Bool\MustNot;
use Kununu\Elasticsearch\Query\Criteria\Bool\Should;
use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\NestableQueryInterface;
use Kununu\Elasticsearch\Query\Criteria\Search;
use Kununu\Elasticsearch\Query\Criteria\SearchInterface;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\SortOrder;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class QueryTest extends TestCase
{
    private const FIELD_NAME_SEARCHES = 'searches';
    private const FIELD_NAME_FILTERS = 'filters';
    private const FIELD_NAME_AGGREGATIONS = 'aggregations';

    /** @dataProvider createDataProvider */
    public function testCreate(array $input): void
    {
        $children = [
            Search::class      => [],
            Filter::class      => [],
            Aggregation::class => [],
        ];

        foreach ($input as $child) {
            $children[$child::class][] = $child;
        }

        $query = Query::create(...$input);

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);
    }

    public static function createDataProvider(): array
    {
        return [
            'empty'                           => [
                'input' => [],
            ],
            'with a filter'                   => [
                'input' => [Filter::create('field', 'value')],
            ],
            'with a search'                   => [
                'input' => [Search::create(['field'], 'value')],
            ],
            'with an aggregation'             => [
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

    /** @dataProvider createDataProvider */
    public function testCreateNested(array $input): void
    {
        $children = [
            Search::class      => [],
            Filter::class      => [],
            Aggregation::class => [],
        ];

        foreach ($input as $child) {
            $children[$child::class][] = $child;
        }

        $path = 'mypath';

        $query = Query::createNested($path, ...$input);

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);
        $this->assertEquals($path, $query->getOption(NestableQueryInterface::OPTION_PATH));
        $this->assertNull($query->getOption(NestableQueryInterface::OPTION_IGNORE_UNMAPPED));
        $this->assertNull($query->getOption(NestableQueryInterface::OPTION_SCORE_MODE));
    }

    /** @dataProvider createDataProvider */
    public function testAdd(array $input): void
    {
        $children = [
            Search::class      => [],
            Filter::class      => [],
            Aggregation::class => [],
        ];

        $query = Query::create();

        $this->assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        $this->assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        $this->assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);

        foreach ($input as $child) {
            $children[$child::class][] = $child;
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

        Query::create(
            new class() implements CriteriaInterface {
                public function toArray(): array
                {
                    return [];
                }
            }
        );
    }

    public function testCreateWithValidAndInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument #1 is of unknown type');

        Query::create(
            Filter::create('field', 'value'),
            new class() implements CriteriaInterface {
                public function toArray(): array
                {
                    return [];
                }
            }
        );
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

    public function testSearchWithInvalidCriteria(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument $search must be one of [%s, %s, %s]',
                SearchInterface::class,
                BoolQueryInterface::class,
                NestableQueryInterface::class
            )
        );

        Query::create()->search(Filter::create('field', 'value'));
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

        $this->assertNull($query->getOption(Query::OPTION_MIN_SCORE));

        $query->setMinScore(42);

        $this->assertEquals(42, $query->getOption(Query::OPTION_MIN_SCORE));
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
                'query'   => ['bool' => ['filter' => ['bool' => ['must' => [['term' => ['field' => 'value']]]]]]],
                '_source' => ['field_a'],
                'size'    => 10,
                'from'    => 1,
                'sort'    => [
                    'field_a' => ['order' => SortOrder::ASC],
                ],
            ],
            $query->toArray()
        );
    }

    /** @dataProvider toArrayDataProvider */
    public function testToArray(Query $query, array $expected): void
    {
        $this->assertEquals($expected, $query->toArray());
    }

    public static function toArrayDataProvider(): array
    {
        return [
            'empty'                                                 => [
                'query'    => Query::create(),
                'expected' => [],
            ],
            'with a min_score'                                      => [
                'query'    => Query::create()->setMinScore(42),
                'expected' => [
                    'min_score' => 42,
                ],
            ],
            'with a filter'                                         => [
                'query'    => Query::create(Filter::create('field', 'value')),
                'expected' => [
                    'query' => ['bool' => ['filter' => ['bool' => ['must' => [['term' => ['field' => 'value']]]]]]],
                ],
            ],
            'with a search, default search operator'                => [
                'query'    => Query::create(Search::create(['field_a'], 'foo')),
                'expected' => [
                    'query' => [
                        'bool' => [
                            'should'               => [['query_string' => ['fields' => ['field_a'], 'query' => 'foo']]],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ],
            'with two searches, AND connected'                      => [
                'query'    => Query::create(
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
            'with an aggregation'                                   => [
                'query'    => Query::create(Aggregation::create('field_a', Metric::SUM, 'my_agg')),
                'expected' => [
                    'aggs' => [
                        'my_agg' => [
                            'sum' => ['field' => 'field_a'],
                        ],
                    ],
                ],
            ],
            'with a little bit of everything'                       => [
                'query'    => Query::create(
                    Filter::create('field', 'value'),
                    Search::create(['field_a'], 'foo'),
                    Aggregation::create('field_a', Metric::SUM, 'my_agg')
                )->setMinScore(42),
                'expected' => [
                    'query'     => [
                        'bool' => [
                            'should'               => [
                                ['query_string' => ['fields' => ['field_a'], 'query' => 'foo']],
                            ],
                            'filter'               => ['bool' => ['must' => [['term' => ['field' => 'value']]]]],
                            'minimum_should_match' => 1,
                        ],
                    ],
                    'aggs'      => [
                        'my_agg' => [
                            'sum' => ['field' => 'field_a'],
                        ],
                    ],
                    'min_score' => 42,
                ],
            ],
            'advanced full text queries combined with bool queries' => [
                'query'    => Query::create(
                    Filter::create('field', 'value')
                )
                    ->search(
                        Must::create(
                            Should::create(
                                Search::create(['field_a'], 'foo', Search::QUERY_STRING),
                                Search::create(['field_a'], 'foo', Search::MATCH)
                            ),
                            Should::create(
                                Search::create(['field_b'], 'foo', Search::QUERY_STRING),
                                Search::create(['field_b'], 'foo', Search::MATCH)
                            )
                        )
                    )
                    ->setSearchOperator(Must::OPERATOR)
                    ->setMinScore(42),
                'expected' => [
                    'query'     => [
                        'bool' => [
                            'must'   => [
                                [
                                    'bool' => [
                                        'must' => [
                                            [
                                                'bool' => [
                                                    'should' => [
                                                        [
                                                            'query_string' => [
                                                                'fields' => ['field_a'],
                                                                'query'  => 'foo',
                                                            ],
                                                        ],
                                                        [
                                                            'match' => ['field_a' => ['query' => 'foo']],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'bool' => [
                                                    'should' => [
                                                        [
                                                            'query_string' => [
                                                                'fields' => ['field_b'],
                                                                'query'  => 'foo',
                                                            ],
                                                        ],
                                                        [
                                                            'match' => ['field_b' => ['query' => 'foo']],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'filter' => ['bool' => ['must' => [['term' => ['field' => 'value']]]]],
                        ],
                    ],
                    'min_score' => 42,
                ],
            ],
            'basic nested query as filter'                          => [
                'query'    => Query::create(
                    Query::createNested('my_field', Filter::create('my_field.subfield', 'foobar'))
                ),
                'expected' => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'nested' => [
                                                'path'  => 'my_field',
                                                'query' => [
                                                    'bool' => [
                                                        'filter' => [
                                                            'bool' => [
                                                                'must' => [
                                                                    [
                                                                        'term' => [
                                                                            'my_field.subfield' => 'foobar',
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'basic nested query as search'                          => [
                'query'    => Query::create()
                    ->search(Query::createNested('my_field', Filter::create('my_field.subfield', 'foobar'))),
                'expected' => [
                    'query' => [
                        'bool' => [
                            'should'               => [
                                [
                                    'nested' => [
                                        'path'  => 'my_field',
                                        'query' => [
                                            'bool' => [
                                                'filter' => [
                                                    'bool' => [
                                                        'must' => [
                                                            [
                                                                'term' => [
                                                                    'my_field.subfield' => 'foobar',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ],
            'nested query with options'                             => [
                'query'    => Query::create(
                    Query::createNested('my_field', Filter::create('my_field.subfield', 'foobar'))
                        ->setOption(NestableQueryInterface::OPTION_SCORE_MODE, 'max')
                        ->setOption(NestableQueryInterface::OPTION_IGNORE_UNMAPPED, true)
                ),
                'expected' => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'nested' => [
                                                'path'            => 'my_field',
                                                'score_mode'      => 'max',
                                                'ignore_unmapped' => true,
                                                'query'           => [
                                                    'bool' => [
                                                        'filter' => [
                                                            'bool' => [
                                                                'must' => [
                                                                    [
                                                                        'term' => [
                                                                            'my_field.subfield' => 'foobar',
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getPublicReflectionProperty(Query $query, string $fieldName): ReflectionProperty
    {
        $property = new ReflectionProperty(Query::class, $fieldName);
        $property->setAccessible(true);

        return $property;
    }

    private function assertChildren(Query $query, string $fieldName, array $expected): void
    {
        $this->assertEquals($expected, $this->getPublicReflectionProperty($query, $fieldName)->getValue($query));
    }
}
