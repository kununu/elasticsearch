<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Aggregation\Builder;

use Kununu\Elasticsearch\Exception\MissingAggregationAttributesException;
use Kununu\Elasticsearch\Query\Aggregation\Builder\CompositeAggregationBuilder;
use Kununu\Elasticsearch\Query\Aggregation\SourceProperty;
use Kununu\Elasticsearch\Query\Aggregation\Sources;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\Filters;
use Kununu\Elasticsearch\Query\Criteria\Operator;
use Kununu\Elasticsearch\Query\RawQuery;
use PHPUnit\Framework\TestCase;

class CompositeAggregationBuilderTest extends TestCase
{
    public function testCompositeAggregationBuilder(): void
    {
        $compositeAggregation = CompositeAggregationBuilder::create()
            ->withName('agg')
            ->withFilters(new Filters(
                new Filter('field', 'value'),
                new Filter('field2', 'value2', Operator::GREATER_THAN_EQUALS)
            ))
            ->withSources(
                new Sources(
                    new SourceProperty('field', 'value')
                )
            );

        $query = $compositeAggregation->getQuery();
        self::assertInstanceOf(RawQuery::class, $query);

        self::assertEquals(
            [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'field' => 'value'
                                ]
                            ],
                            [
                                'range' => [
                                    'field2' => [
                                        'gte' => 'value2'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'agg' => [
                        'composite' => [
                            'size' => 100,
                            'sources' => [
                                [
                                    'field' => [
                                        'terms' => [
                                            'field' => 'value',
                                            'missing_bucket' => false,
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            $compositeAggregation->toArray()
        );

        $compositeAggregation->withAfterKey(['field' => 'value']);

        self::assertEquals(
            [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'field' => 'value'
                                ]
                            ],
                            [
                                'range' => [
                                    'field2' => [
                                        'gte' => 'value2'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'agg' => [
                        'composite' => [
                            'size' => 100,
                            'sources' => [
                                [
                                    'field' => [
                                        'terms' => [
                                            'field' => 'value',
                                            'missing_bucket' => false,
                                        ]
                                    ]
                                ]
                            ],
                            'after' => ['field' => 'value']
                        ]
                    ]
                ],
            ],
            $compositeAggregation->toArray()
        );
    }

    public function testCompositeAggregationBuilderWithNoAggregationName()
    {
        self::expectException(MissingAggregationAttributesException::class);
        self::expectExceptionMessage('Aggregation name is missing');

        $compositeAggregation = CompositeAggregationBuilder::create()
            ->withFilters(new Filters(
                new Filter('field', 'value'),
                new Filter('field2', 'value2', Operator::GREATER_THAN_EQUALS)
            ))
            ->withSources(
                new Sources(
                    new SourceProperty('field', 'value')
                )
            );

        $compositeAggregation->toArray();
    }
}
