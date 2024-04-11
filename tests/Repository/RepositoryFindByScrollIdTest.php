<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;

final class RepositoryFindByScrollIdTest extends AbstractRepositoryTestCase
{
    #[DataProvider('searchResultDataProvider')]
    public function testFindByScrollId(array $esResult, mixed $endResult): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepository()->findByScrollId($scrollId);

        self::assertEquals($endResult, $result->asArray());
    }

    #[DataProvider('searchResultDataProvider')]
    public function testFindByScrollIdCanOverrideScrollContextKeepalive(array $esResult, array $endResult): void
    {
        $scrollId = 'foobar';
        $keepalive = '20m';

        $this->clientMock
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => $keepalive,
            ])
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepository()->findByScrollId($scrollId, $keepalive);

        self::assertEquals($endResult, $result->asArray());
    }

    #[DataProvider('searchResultWithEntitiesDataProvider')]
    public function testFindByScrollIdWithEntityFactory(array $esResult, array $endResult): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->willReturn(array_merge($esResult, ['_scroll_id' => $scrollId]));

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepository(['entity_factory' => new EntityFactoryStub()])->findByScrollId($scrollId);

        self::assertEquals($endResult, $result->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        self::assertEquals($scrollId, $result->getScrollId());

        if ($result->getCount() > 0) {
            foreach ($result as $entity) {
                self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    #[DataProvider('searchResultWithEntitiesDataProvider')]
    public function testFindByScrollIdWithEntityClass(array $esResult, array $endResult): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body'   => [
                    'scroll_id' => $scrollId,
                ],
                'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->willReturn(array_merge($esResult, ['_scroll_id' => $scrollId]));

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this
            ->getRepository(['entity_class' => PersistableEntityStub::class])
            ->findByScrollId($scrollId);

        self::assertEquals($endResult, $result->asArray());
        self::assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        self::assertEquals($scrollId, $result->getScrollId());

        if ($result->getCount() > 0) {
            foreach ($result as $entity) {
                self::assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    public function testFindByScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->expects(self::once())
            ->method('scroll')
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
                'scroll'    => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->findByScrollId($scrollId);
        } catch (ReadOperationException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
