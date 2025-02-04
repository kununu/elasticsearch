<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use Kununu\Elasticsearch\Result\ResultIterator;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractFindByQueryTestCase extends AbstractRepositoryTestCase
{
    #[DataProvider('queryAndSearchResultVariationsDataProvider')]
    public function testFindByQuery(QueryInterface $query, array $result, mixed $endResult, bool $scroll): void
    {
        $rawParams = [
            'index' => self::INDEX['read'],
            'body'  => $query->toArray(),
        ];

        if ($scroll) {
            $rawParams['scroll'] = RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
        }

        $this->client
            ->expects(self::once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($result);

        $repository = $this->getRepository();

        $result = $scroll ? $repository->findScrollableByQuery($query) : $repository->findByQuery($query);

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
        array $result,
        array $endResult,
        bool $scroll,
    ): void {
        $rawParams = [
            'index' => self::INDEX['read'],
            'body'  => $query->toArray(),
        ];

        if ($scroll) {
            $rawParams['scroll'] = RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
        }

        $this->client
            ->expects(self::once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repository = $this->getRepositoryWithEntityFactory();

        $repositoryResult = $scroll
            ? $repository->findScrollableByQuery($query)
            : $repository->findByQuery($query);

        self::assertInstanceOf(ResultIterator::class, $repositoryResult);
        self::assertEquals($endResult, $repositoryResult->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $repositoryResult->getTotal());
        if ($scroll) {
            self::assertEquals(self::SCROLL_ID, $repositoryResult->getScrollId());
        } else {
            self::assertNull($repositoryResult->getScrollId());
        }
        foreach ($repositoryResult as $entity) {
            self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
        }
    }

    #[DataProvider('queryAndSearchResultVariationsWithEntitiesDataProvider')]
    public function testFindByQueryWithEntityClass(
        QueryInterface $query,
        array $result,
        array $endResult,
        bool $scroll,
    ): void {
        $rawParams = [
            'index' => self::INDEX['read'],
            'body'  => $query->toArray(),
        ];

        if ($scroll) {
            $rawParams['scroll'] = RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
        }

        $this->client
            ->expects(self::once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repository = $this->getRepositoryWithEntityClass();

        $repositoryResult = $scroll
            ? $repository->findScrollableByQuery($query)
            : $repository->findByQuery($query);

        self::assertInstanceOf(ResultIterator::class, $repositoryResult);
        self::assertEquals($endResult, $repositoryResult->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $repositoryResult->getTotal());
        if ($scroll) {
            self::assertEquals(self::SCROLL_ID, $repositoryResult->getScrollId());
        } else {
            self::assertNull($repositoryResult->getScrollId());
        }

        foreach ($repositoryResult as $entity) {
            self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
        }
    }

    public function testFindByQueryFails(): void
    {
        $this->client
            ->expects(self::once())
            ->method('search')
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->findByQuery(Query::create());
        } catch (ReadOperationException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
