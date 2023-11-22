<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;

final class RepositoryFindByQueryTest extends AbstractRepositoryTestCase
{
    /** @dataProvider queryAndSearchResultVariationsDataProvider */
    public function testFindByQuery(QueryInterface $query, array $esResult, mixed $endResult, bool $scroll): void
    {
        $rawParams = [
            'index' => self::INDEX['read'],
            'body'  => $query->toArray(),
        ];

        if ($scroll) {
            $rawParams['scroll'] = RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
        }

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with($rawParams)
            ->andReturn($esResult);

        $result = $scroll
            ? $this->getRepository()->findScrollableByQuery($query)
            : $this->getRepository()->findByQuery($query);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            $this->assertNull($result->getScrollId());
        }
    }

    /** @dataProvider queryAndSearchResultVariationsWithEntitiesDataProvider */
    public function testFindByQueryWithEntityFactory(
        QueryInterface $query,
        array $esResult,
        array $endResult,
        bool $scroll
    ): void {
        $rawParams = [
            'index' => self::INDEX['read'],
            'body'  => $query->toArray(),
        ];

        if ($scroll) {
            $rawParams['scroll'] = RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
        }

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with($rawParams)
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $repository = $this->getRepository(['entity_factory' => $this->getEntityFactory()]);

        $result = $scroll
            ? $repository->findScrollableByQuery($query)
            : $repository->findByQuery($query);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            $this->assertNull($result->getScrollId());
        }

        if ($result->getCount() > 0) {
            foreach ($result as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    /** @dataProvider queryAndSearchResultVariationsWithEntitiesDataProvider */
    public function testFindByQueryWithEntityClass(
        QueryInterface $query,
        array $esResult,
        array $endResult,
        bool $scroll
    ): void {
        $rawParams = [
            'index' => self::INDEX['read'],
            'body'  => $query->toArray(),
        ];

        if ($scroll) {
            $rawParams['scroll'] = RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
        }

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with($rawParams)
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $repository = $this->getRepository(['entity_class' => get_class($this->getEntityClassInstance())]);

        $result = $scroll
            ? $repository->findScrollableByQuery($query)
            : $repository->findByQuery($query);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            $this->assertNull($result->getScrollId());
        }

        if ($result->getCount() > 0) {
            foreach ($result as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    public function testFindByQueryFails(): void
    {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->findByQuery(Query::create());
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
    }
}
