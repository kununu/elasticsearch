<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use Kununu\Elasticsearch\Result\ResultIterator;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractFindByScrollIdTestCase extends AbstractRepositoryTestCase
{
    #[DataProvider('searchResultDataProvider')]
    public function testFindByScrollId(array $result, mixed $endResult): void
    {
        $scrollId = 'foobar';

        $this->client
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repositoryResult = $this->getRepository()->findByScrollId($scrollId);

        self::assertEquals($endResult, $repositoryResult->asArray());
    }

    #[DataProvider('searchResultDataProvider')]
    public function testFindByScrollIdCanOverrideScrollContextKeepalive(array $result, array $endResult): void
    {
        $scrollId = 'foobar';
        $keepalive = '20m';

        $this->client
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => $keepalive,
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repositoryResult = $this->getRepository()->findByScrollId($scrollId, $keepalive);

        self::assertEquals($endResult, $repositoryResult->asArray());
    }

    #[DataProvider('searchResultWithEntitiesDataProvider')]
    public function testFindByScrollIdWithEntityFactory(array $result, array $endResult): void
    {
        $scrollId = 'foobar';

        $this->client
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->willReturn(array_merge($result, ['_scroll_id' => $scrollId]));

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repositoryResult = $this->getRepositoryWithEntityFactory()->findByScrollId($scrollId);

        self::assertInstanceOf(ResultIterator::class, $repositoryResult);
        self::assertEquals($endResult, $repositoryResult->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $repositoryResult->getTotal());
        self::assertEquals($scrollId, $repositoryResult->getScrollId());
        foreach ($repositoryResult as $entity) {
            self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
        }
    }

    #[DataProvider('searchResultWithEntitiesDataProvider')]
    public function testFindByScrollIdWithEntityClass(array $result, array $endResult): void
    {
        $scrollId = 'foobar';

        $this->client
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->willReturn(array_merge($result, ['_scroll_id' => $scrollId]));

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repositoryResult = $this->getRepositoryWithEntityClass()->findByScrollId($scrollId);

        self::assertInstanceOf(ResultIterator::class, $repositoryResult);
        self::assertEquals($endResult, $repositoryResult->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $repositoryResult->getTotal());
        self::assertEquals($scrollId, $repositoryResult->getScrollId());
        foreach ($repositoryResult as $entity) {
            self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
        }
    }

    public function testFindByScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->client
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->findByScrollId($scrollId);
        } catch (ReadOperationException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
