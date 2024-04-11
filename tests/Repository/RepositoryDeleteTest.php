<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Exception;
use Kununu\Elasticsearch\Exception\DeleteException;
use Kununu\Elasticsearch\Exception\DocumentNotFoundException;
use Kununu\Elasticsearch\Repository\Repository;

final class RepositoryDeleteTest extends AbstractRepositoryTestCase
{
    public function testDelete(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->delete(self::ID);
    }

    public function testDeleteWithForcedRefresh(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index'   => self::INDEX['write'],
                'id'      => self::ID,
                'refresh' => true,
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['force_refresh_on_write' => true])->delete(self::ID);
    }

    public function testDeleteFails(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->delete(self::ID);
        } catch (DeleteException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals(self::ID, $e->getDocumentId());
        }
    }

    public function testDeleteFailsBecauseDocumentNotFound(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ])
            ->willThrowException(new Missing404Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        try {
            $this->getRepository()->delete(self::ID);
        } catch (DocumentNotFoundException $e) {
            self::assertEquals(self::ERROR_PREFIX . 'No document found with id ' . self::ID, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals(self::ID, $e->getDocumentId());
        }
    }

    public function testPostDeleteIsCalled(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $manager = new class($this->clientMock, ['index_write' => self::INDEX['write']]) extends Repository {
            protected function postDelete(string $id): void
            {
                AbstractRepositoryTestCase::assertEquals(AbstractRepositoryTestCase::ID, $id);
            }
        };

        $manager->delete(self::ID);
    }
}
