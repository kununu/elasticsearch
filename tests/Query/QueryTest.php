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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class QueryTest extends TestCase
{
    private const FIELD_NAME_SEARCHES = 'searches';
    private const FIELD_NAME_FILTERS = 'filters';
    private const FIELD_NAME_AGGREGATIONS = 'aggregations';

    #[DataProvider('createDataProvider')]
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

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);
    }

    public static function createDataProvider(): array
    {
        return [
            'empty'                           => [
                'input' => [],
            ],
            'with_a_filter'                   => [
                'input' => [Filter::create('field', 'value')],
            ],
            'with_a_search'                   => [
                'input' => [Search::create(['field'], 'value')],
            ],
            'with_an_aggregation'             => [
                'input' => [Aggregation::create('field', Metric::SUM)],
            ],
            'with_a_little_bit_of_everything' => [
                'input' => [
                    Filter::create('field', 'value'),
                    Search::create(['field'], 'value'),
                    Aggregation::create('field', Metric::SUM),
                ],
            ],
        ];
    }

    #[DataProvider('createDataProvider')]
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

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);
        self::assertEquals($path, $query->getOption(NestableQueryInterface::OPTION_PATH));
        self::assertNull($query->getOption(NestableQueryInterface::OPTION_IGNORE_UNMAPPED));
        self::assertNull($query->getOption(NestableQueryInterface::OPTION_SCORE_MODE));
    }

    #[DataProvider('createDataProvider')]
    public function testAdd(array $input): void
    {
        $children = [
            Search::class      => [],
            Filter::class      => [],
            Aggregation::class => [],
        ];

        $query = Query::create();

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);

        foreach ($input as $child) {
            $children[$child::class][] = $child;
            $query->add($child);
        }

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, $children[Search::class]);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, $children[Filter::class]);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, $children[Aggregation::class]);
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

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, []);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);

        $search = Search::create(['field'], 'value');
        $query->search($search);

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, [$search]);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, []);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);
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

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, []);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);

        $filter = Filter::create('field', 'value');
        $query->where($filter);

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, [$filter]);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);
    }

    public function testAggregate(): void
    {
        $query = Query::create();

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, []);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, []);

        $aggregation = Aggregation::create('field', Metric::SUM);
        $query->aggregate($aggregation);

        self::assertChildren($query, self::FIELD_NAME_SEARCHES, []);
        self::assertChildren($query, self::FIELD_NAME_FILTERS, []);
        self::assertChildren($query, self::FIELD_NAME_AGGREGATIONS, [$aggregation]);
    }

    public function testMinScore(): void
    {
        $query = Query::create();

        self::assertNull($query->getOption(Query::OPTION_MIN_SCORE));

        $query->setMinScore(42);

        self::assertEquals(42, $query->getOption(Query::OPTION_MIN_SCORE));
    }

    public function testSearchOperator(): void
    {
        $query = Query::create();

        self::assertEquals(Should::OPERATOR, $query->getSearchOperator());

        $query->setSearchOperator(Must::OPERATOR);

        self::assertEquals(Must::OPERATOR, $query->getSearchOperator());
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

        self::assertEquals(
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

    #[DataProvider('toArrayDataProvider')]
    public function testToArray(Query $query, array $expected): void
    {
        self::assertEquals($expected, $query->toArray());
    }

    public static function toArrayDataProvider(): array
    {
        return array_merge(
            self::toArrayBatch1(),
            self::toArrayBatch2(),
            self::toArrayBatch3()
        );
    }

    private static function toArrayBatch1(): array
    {
        return [
            'empty'                                     => [
                'query'    => Query::create(),
                'expected' => [],
            ],
            'with_a_min_score'                          => [
                'query'    => Query::create()->setMinScore(42),
                'expected' => [
                    'min_score' => 42,
                ],
            ],
            'with_a_filter'                             => [
                'query'    => Query::create(Filter::create('field', 'value')),
                'expected' => [
                    'query' => ['bool' => ['filter' => ['bool' => ['must' => [['term' => ['field' => 'value']]]]]]],
                ],
            ],
            'with_a_search_and_default_search_operator' => [
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
        ];
    }

    private static function toArrayBatch2(): array
    {
        return [
            'with_two_searches_connected_by_and'                    => [
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
            'with_an_aggregation'                                   => [
                'query'    => Query::create(Aggregation::create('field_a', Metric::SUM, 'my_agg')),
                'expected' => [
                    'aggs' => [
                        'my_agg' => [
                            'sum' => ['field' => 'field_a'],
                        ],
                    ],
                ],
            ],
            'with_a_little_bit_of_everything'                       => [
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
            'advanced_full_text_queries_combined_with_bool_queries' => [
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
        ];
    }

    private static function toArrayBatch3(): array
    {
        return [
            'basic_nested_query_as_filter' => [
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
            'basic_nested_query_as_search' => [
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
            'nested_query_with_options'    => [
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

    private static function getPublicReflectionProperty(Query $query, string $fieldName): ReflectionProperty
    {
        return new ReflectionProperty($query, $fieldName);
    }

    private static function assertChildren(Query $query, string $fieldName, array $expected): void
    {
        self::assertEquals($expected, self::getPublicReflectionProperty($query, $fieldName)->getValue($query));
    }
}
