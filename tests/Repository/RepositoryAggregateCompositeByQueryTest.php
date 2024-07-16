<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Query\CompositeAggregationQueryInterface;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\CompositeResult;

final class RepositoryAggregateCompositeByQueryTest extends AbstractRepositoryTestCase
{
    public function testAggregateCompositeByQuery(): void
    {
        $query = $this->createMock(QueryInterface::class);
        $compositeQuery = $this->createMock(CompositeAggregationQueryInterface::class);

        $query
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(
                [
                    'aggs' => [
                        'agg' => [
                            'composite' => [
                                'size' => 100,
                                'sources' => [
                                    ['source' => ['terms' => ['field' => 'property', 'missing_bucket' => false]]],
                                ],
                            ],
                        ],
                    ]
                ]
            );

        $compositeQuery
            ->expects($this->any())
            ->method('getName')
            ->willReturn('agg');

        $compositeQuery
            ->expects($this->once())
            ->method('withAfterKey')
            ->with(null)
            ->willReturn($compositeQuery);

        $compositeQuery
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->clientMock
            ->expects($this->once())
            ->method('search')
            ->with(
                [
                    'index' => 'some_index_read',
                    'body' => [
                        #'size' => 0,
                        'aggs' => [
                            'agg' => [
                                'composite' => [
                                    'size' => 100,
                                    'sources' => [
                                        ['source' => ['terms' => ['field' => 'property', 'missing_bucket' => false]]],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ]
            )
            ->willReturn([
                'aggregations' => [
                    'agg' => [
                        'buckets' => [
                            ['key' => ['property' => 'value1'], 'doc_count' => 1],
                            ['key' => ['property' => 'value2'], 'doc_count' => 30],
                        ],
                    ],
                ],
            ]);

        $compositeResults = [];
        foreach ($this->getRepository()->aggregateCompositeByQuery($compositeQuery) as $item) {
            $compositeResults[] = $item;
        }

        self::assertCount(2, $compositeResults);
        self::assertEquals([
            new CompositeResult(['property' => 'value1'], 1, 'agg'),
            new CompositeResult(['property' => 'value2'], 30, 'agg')
        ], $compositeResults);
    }
}
