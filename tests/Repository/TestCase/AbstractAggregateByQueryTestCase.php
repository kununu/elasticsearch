<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Aggregation;
use Kununu\Elasticsearch\Query\Aggregation\Metric;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\AggregationResult;
use Kununu\Elasticsearch\Result\ResultIterator;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractAggregateByQueryTestCase extends AbstractRepositoryTestCase
{
    #[DataProvider('queryAndSearchResultDataProvider')]
    public function testAggregateByQuery(QueryInterface $query, array $result): void
    {
        $this->client
            ->expects(self::once())
            ->method('search')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->willReturn(
                array_merge(
                    $result,
                    [
                        'aggregations' => [
                            'my_aggregation' => [
                                'value' => 0.1,
                            ],
                        ],
                    ]
                )
            );

        $aggregationResult = $this->getRepository()->aggregateByQuery($query);
        $documents = $aggregationResult->getDocuments();
        $aggregation = $aggregationResult->getResultByName('my_aggregation');

        self::assertInstanceOf(ResultIterator::class, $documents);
        self::assertEquals(count($result['hits']['hits']), $documents->getCount());
        self::assertEquals(self::DOCUMENT_COUNT, $documents->getTotal());
        self::assertCount(count($result['hits']['hits']), $documents);
        self::assertNull($documents->getScrollId());
        self::assertEquals($result['hits']['hits'], $documents->asArray());
        self::assertCount(1, $aggregationResult->getResults());
        self::assertInstanceOf(AggregationResult::class, $aggregation);
        self::assertEquals('my_aggregation', $aggregation->getName());
        self::assertEquals(0.1, $aggregation->getValue());
    }

    #[DataProvider('queryAndSearchResultWithEntitiesDataProvider')]
    public function testAggregateByQueryWithEntityFactory(
        QueryInterface $query,
        array $result,
        mixed $endResult,
    ): void {
        $this->client
            ->expects(self::once())
            ->method('search')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->willReturn(
                array_merge(
                    $result,
                    [
                        'aggregations' => [
                            'my_aggregation' => [
                                'value' => 0.1,
                            ],
                        ],
                    ]
                )
            );

        $aggregationResult = $this->getRepositoryWithEntityFactory()->aggregateByQuery($query);
        $documents = $aggregationResult->getDocuments();
        $aggregation = $aggregationResult->getResultByName('my_aggregation');

        self::assertInstanceOf(ResultIterator::class, $documents);
        self::assertEquals(count($result['hits']['hits']), $documents->getCount());
        self::assertEquals(self::DOCUMENT_COUNT, $documents->getTotal());
        self::assertCount(count($result['hits']['hits']), $documents);
        self::assertNull($documents->getScrollId());
        self::assertEquals($endResult, $documents->asArray());
        foreach ($documents as $entity) {
            self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
        }
        self::assertCount(1, $aggregationResult->getResults());
        self::assertInstanceOf(AggregationResult::class, $aggregation);
        self::assertEquals('my_aggregation', $aggregation->getName());
        self::assertEquals(0.1, $aggregation->getValue());
    }

    #[DataProvider('queryAndSearchResultWithEntitiesDataProvider')]
    public function testAggregateByQueryWithEntityClass(
        QueryInterface $query,
        array $result,
        mixed $endResult,
    ): void {
        $this->client
            ->expects(self::once())
            ->method('search')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->willReturn(
                array_merge(
                    $result,
                    [
                        'aggregations' => [
                            'my_aggregation' => [
                                'value' => 0.1,
                            ],
                        ],
                    ]
                )
            );

        $aggregationResult = $this->getRepositoryWithEntityClass()->aggregateByQuery($query);
        $documents = $aggregationResult->getDocuments();
        $aggregation = $aggregationResult->getResultByName('my_aggregation');

        self::assertInstanceOf(ResultIterator::class, $documents);
        self::assertEquals(count($result['hits']['hits']), $documents->getCount());
        self::assertEquals(self::DOCUMENT_COUNT, $documents->getTotal());
        self::assertCount(count($result['hits']['hits']), $documents);
        self::assertNull($documents->getScrollId());
        self::assertEquals($endResult, $documents->asArray());
        foreach ($documents as $entity) {
            self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
        }
        self::assertCount(1, $aggregationResult->getResults());
        self::assertInstanceOf(AggregationResult::class, $aggregation);
        self::assertEquals('my_aggregation', $aggregation->getName());
        self::assertEquals(0.1, $aggregation->getValue());
    }

    public function testAggregateByQueryFails(): void
    {
        $query = Query::create(
            Filter::create('foo', 'bar'),
            Aggregation::create('foo', Metric::EXTENDED_STATS)
        );

        $this->client
            ->expects(self::once())
            ->method('search')
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->aggregateByQuery($query);
        } catch (ReadOperationException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
