<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Client;
use Kununu\Elasticsearch\Query\Aggregation\SourceProperty;
use Kununu\Elasticsearch\Query\Aggregation\Sources;
use Kununu\Elasticsearch\Query\Criteria\Filters;
use Kununu\Elasticsearch\Repository\CompositeAggregationRepository;
use Kununu\Elasticsearch\Result\CompositeResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CompositeAggregationRepositoryTest extends TestCase
{
    private Client|MockObject $client;

    private CompositeAggregationRepository|MockObject $repository;

    public function testLookup(): void
    {
        $this->client
            ->expects($this->once())
            ->method('search')
            ->with(
                [
                    'index' => 'index_read',
                    'body' => [
                        'size' => 0,
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
                            ['key' => ['source' => 'value1'], 'doc_count' => 1],
                            ['key' => ['source' => 'value2'], 'doc_count' => 30],
                        ],
                    ],
                ],
            ]);

        $generator = $this->repository->lookup(
            new Filters(),
            new Sources(new SourceProperty('source', 'property')),
            'agg'
        );

        $items = [];
        foreach ($generator as $item) {
            $items[] = $item;
        }

        self::assertCount(2, $items);
        self::assertEquals([
            new CompositeResult(['source' => 'value1'], 1, 'agg'),
            new CompositeResult(['source' => 'value2'], 30, 'agg')
        ], $items);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
        $this->repository = new CompositeAggregationRepository(
            $this->client,
            [
                'index_read' => 'index_read',
                'index_write' => 'index_write',
                'type' => '_doc',
            ]
        );
    }
}
