<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;

final class RepositoryFindByQueryTest extends AbstractRepositoryTestCase
{
    #[DataProvider('queryAndSearchResultVariationsDataProvider')]
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
            ->expects(self::once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($esResult);

        $result = $scroll
            ? $this->getRepository()->findScrollableByQuery($query)
            : $this->getRepository()->findByQuery($query);

        self::assertEquals($endResult, $result->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            self::assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            self::assertNull($result->getScrollId());
        }
    }

    #[DataProvider('queryAndSearchResultVariationsWithEntitiesDataProvider')]
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
            ->expects(self::once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $repository = $this->getRepository(['entity_factory' => new EntityFactoryStub()]);

        $result = $scroll
            ? $repository->findScrollableByQuery($query)
            : $repository->findByQuery($query);

        self::assertEquals($endResult, $result->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            self::assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            self::assertNull($result->getScrollId());
        }

        if ($result->getCount() > 0) {
            foreach ($result as $entity) {
                self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    #[DataProvider('queryAndSearchResultVariationsWithEntitiesDataProvider')]
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
            ->expects(self::once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $repository = $this->getRepository(['entity_class' => PersistableEntityStub::class]);

        $result = $scroll
            ? $repository->findScrollableByQuery($query)
            : $repository->findByQuery($query);

        self::assertEquals($endResult, $result->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            self::assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            self::assertNull($result->getScrollId());
        }

        if ($result->getCount() > 0) {
            foreach ($result as $entity) {
                self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    public function testFindByQueryFails(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('search')
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->findByQuery(Query::create());
        } catch (ReadOperationException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
