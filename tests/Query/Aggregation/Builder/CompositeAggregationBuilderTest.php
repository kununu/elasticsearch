<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Aggregation\Builder;

use Kununu\Elasticsearch\Exception\MissingAggregationAttributesException;
use Kununu\Elasticsearch\Query\Aggregation\Builder\CompositeAggregationQueryBuilder;
use Kununu\Elasticsearch\Query\Aggregation\SourceProperty;
use Kununu\Elasticsearch\Query\Aggregation\Sources;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\Filters;
use Kununu\Elasticsearch\Query\Criteria\Operator;
use Kununu\Elasticsearch\Query\RawQuery;
use PHPUnit\Framework\TestCase;

final class CompositeAggregationBuilderTest extends TestCase
{
    public function testCompositeAggregationBuilder(): void
    {
        $compositeAggregation = CompositeAggregationQueryBuilder::create()
            ->withName('agg')
            ->withFilters(new Filters(
                new Filter('field', 'value'),
                new Filter('field2', 'value2', Operator::GREATER_THAN_EQUALS)
            ))
            ->withSources(
                new Sources(
                    new SourceProperty('source', 'property')
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
                                    'field' => 'value',
                                ],
                            ],
                            [
                                'range' => [
                                    'field2' => [
                                        'gte' => 'value2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'aggs' => [
                    'agg' => [
                        'composite' => [
                            'size'    => 100,
                            'sources' => [
                                [
                                    'source' => [
                                        'terms' => [
                                            'field'          => 'property',
                                            'missing_bucket' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
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
                                    'field' => 'value',
                                ],
                            ],
                            [
                                'range' => [
                                    'field2' => [
                                        'gte' => 'value2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'aggs' => [
                    'agg' => [
                        'composite' => [
                            'size'    => 100,
                            'sources' => [
                                [
                                    'source' => [
                                        'terms' => [
                                            'field'          => 'property',
                                            'missing_bucket' => false,
                                        ],
                                    ],
                                ],
                            ],
                            'after' => ['field' => 'value'],
                        ],
                    ],
                ],
            ],
            $compositeAggregation->toArray()
        );
    }

    public function testCompositeAggregationBuilderWithNoAggregationName(): void
    {
        $this->expectException(MissingAggregationAttributesException::class);
        $this->expectExceptionMessage('Aggregation name is missing');

        $compositeAggregation = CompositeAggregationQueryBuilder::create()
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
