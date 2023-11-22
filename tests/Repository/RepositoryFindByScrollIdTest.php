<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;

final class RepositoryFindByScrollIdTest extends AbstractRepositoryTestCase
{
    /** @dataProvider searchResultDataProvider */
    public function testFindByScrollId(array $esResult, mixed $endResult): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->shouldReceive('scroll')
            ->once()
            ->with([
                'scroll_id' => $scrollId,
                'scroll'    => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository()->findByScrollId($scrollId);

        $this->assertEquals($endResult, $result->asArray());
    }

    /** @dataProvider searchResultDataProvider */
    public function testFindByScrollIdCanOverrideScrollContextKeepalive(array $esResult, array $endResult): void
    {
        $scrollId = 'foobar';
        $keepalive = '20m';

        $this->clientMock
            ->shouldReceive('scroll')
            ->once()
            ->with([
                'scroll_id' => $scrollId,
                'scroll'    => $keepalive,
            ])
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository()->findByScrollId($scrollId, $keepalive);

        $this->assertEquals($endResult, $result->asArray());
    }

    /** @dataProvider searchResultWithEntitiesDataProvider */
    public function testFindByScrollIdWithEntityFactory(array $esResult, array $endResult): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->shouldReceive('scroll')
            ->once()
            ->with([
                'scroll_id' => $scrollId,
                'scroll'    => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->andReturn(array_merge($esResult, ['_scroll_id' => $scrollId]));

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository(['entity_factory' => $this->getEntityFactory()])->findByScrollId($scrollId);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        $this->assertEquals($scrollId, $result->getScrollId());

        if ($result->getCount() > 0) {
            foreach ($result as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    /** @dataProvider searchResultWithEntitiesDataProvider */
    public function testFindByScrollIdWithEntityClass(array $esResult, array $endResult): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->shouldReceive('scroll')
            ->once()
            ->with([
                'scroll_id' => $scrollId,
                'scroll'    => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->andReturn(array_merge($esResult, ['_scroll_id' => $scrollId]));

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this
            ->getRepository(['entity_class' => $this->getEntityClass()])
            ->findByScrollId($scrollId);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        $this->assertEquals($scrollId, $result->getScrollId());

        if ($result->getCount() > 0) {
            foreach ($result as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    public function testFindByScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->shouldReceive('scroll')
            ->once()
            ->with([
                'scroll_id' => $scrollId,
                'scroll'    => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
            ])
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->findByScrollId($scrollId);
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
    }
}
