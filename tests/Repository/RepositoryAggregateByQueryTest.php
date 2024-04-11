<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Aggregation;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use PHPUnit\Framework\Attributes\DataProvider;

final class RepositoryAggregateByQueryTest extends AbstractRepositoryTestCase
{
    #[DataProvider('queryAndSearchResultDataProvider')]
    public function testAggregateByQuery(QueryInterface $query, array $esResult): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('search')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->willReturn(array_merge($esResult, ['aggregations' => ['my_aggregation' => ['value' => 0.1]]]));

        $aggregationResult = $this->getRepository()->aggregateByQuery($query);

        self::assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        self::assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        self::assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        self::assertNull($aggregationResult->getDocuments()->getScrollId());
        self::assertEquals($esResult['hits']['hits'], $aggregationResult->getDocuments()->asArray());

        self::assertCount(1, $aggregationResult->getResults());
        self::assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        self::assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    #[DataProvider('queryAndSearchResultWithEntitiesDataProvider')]
    public function testAggregateByQueryWithEntityFactory(
        QueryInterface $query,
        array $esResult,
        mixed $endResult
    ): void {
        $this->clientMock
            ->expects(self::once())
            ->method('search')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->willReturn(array_merge($esResult, ['aggregations' => ['my_aggregation' => ['value' => 0.1]]]));

        $aggregationResult = $this
            ->getRepository(['entity_factory' => new EntityFactoryStub()])
            ->aggregateByQuery($query);

        self::assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        self::assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        self::assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        self::assertNull($aggregationResult->getDocuments()->getScrollId());
        self::assertEquals($endResult, $aggregationResult->getDocuments()->asArray());

        if (!empty($aggregationResult->getDocuments())) {
            foreach ($aggregationResult->getDocuments() as $entity) {
                self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }

        self::assertCount(1, $aggregationResult->getResults());
        self::assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        self::assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    #[DataProvider('queryAndSearchResultWithEntitiesDataProvider')]
    public function testAggregateByQueryWithEntityClass(
        QueryInterface $query,
        array $esResult,
        mixed $endResult
    ): void {
        $this->clientMock
            ->expects(self::once())
            ->method('search')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->willReturn(array_merge($esResult, ['aggregations' => ['my_aggregation' => ['value' => 0.1]]]));

        $aggregationResult = $this
            ->getRepository(['entity_class' => PersistableEntityStub::class])
            ->aggregateByQuery($query);

        self::assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        self::assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        self::assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        self::assertNull($aggregationResult->getDocuments()->getScrollId());
        self::assertEquals($endResult, $aggregationResult->getDocuments()->asArray());

        if (!empty($aggregationResult->getDocuments())) {
            foreach ($aggregationResult->getDocuments() as $entity) {
                self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }

        self::assertCount(1, $aggregationResult->getResults());
        self::assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        self::assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    public function testAggregateByQueryFails(): void
    {
        $query = Query::create(
            Filter::create('foo', 'bar'),
            Aggregation::create('foo', Aggregation\Metric::EXTENDED_STATS)
        );

        $this->clientMock
            ->expects(self::once())
            ->method('search')
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->aggregateByQuery($query);
        } catch (ReadOperationException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
