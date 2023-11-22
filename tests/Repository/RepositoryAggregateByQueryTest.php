<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Aggregation;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;

final class RepositoryAggregateByQueryTest extends AbstractRepositoryTestCase
{
    /** @dataProvider queryAndSearchResultDataProvider */
    public function testAggregateByQuery(QueryInterface $query, array $esResult): void
    {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->andReturn(array_merge($esResult, ['aggregations' => ['my_aggregation' => ['value' => 0.1]]]));

        $aggregationResult = $this->getRepository()->aggregateByQuery($query);

        $this->assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        $this->assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        $this->assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        $this->assertNull($aggregationResult->getDocuments()->getScrollId());
        $this->assertEquals($esResult['hits']['hits'], $aggregationResult->getDocuments()->asArray());

        $this->assertEquals(1, count($aggregationResult->getResults()));
        $this->assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        $this->assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    /** @dataProvider queryAndSearchResultWithEntitiesDataProvider */
    public function testAggregateByQueryWithEntityFactory(
        QueryInterface $query,
        array $esResult,
        mixed $endResult
    ): void {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->andReturn(array_merge($esResult, ['aggregations' => ['my_aggregation' => ['value' => 0.1]]]));

        $aggregationResult = $this
            ->getRepository(['entity_factory' => $this->getEntityFactory()])
            ->aggregateByQuery($query);

        $this->assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        $this->assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        $this->assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        $this->assertNull($aggregationResult->getDocuments()->getScrollId());
        $this->assertEquals($endResult, $aggregationResult->getDocuments()->asArray());

        if (!empty($aggregationResult->getDocuments())) {
            foreach ($aggregationResult->getDocuments() as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }

        $this->assertEquals(1, count($aggregationResult->getResults()));
        $this->assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        $this->assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    /** @dataProvider queryAndSearchResultWithEntitiesDataProvider */
    public function testAggregateByQueryWithEntityClass(
        QueryInterface $query,
        array $esResult,
        mixed $endResult
    ): void {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->andReturn(array_merge($esResult, ['aggregations' => ['my_aggregation' => ['value' => 0.1]]]));

        $aggregationResult = $this
            ->getRepository(['entity_class' => $this->getEntityClass()])
            ->aggregateByQuery($query);

        $this->assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        $this->assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        $this->assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        $this->assertNull($aggregationResult->getDocuments()->getScrollId());
        $this->assertEquals($endResult, $aggregationResult->getDocuments()->asArray());

        if (!empty($aggregationResult->getDocuments())) {
            foreach ($aggregationResult->getDocuments() as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }

        $this->assertCount(1, $aggregationResult->getResults());
        $this->assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        $this->assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    public function testAggregateByQueryFails(): void
    {
        $query = Query::create(
            Filter::create('foo', 'bar'),
            Aggregation::create('foo', Aggregation\Metric::EXTENDED_STATS)
        );

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->aggregateByQuery($query);
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
    }
}
