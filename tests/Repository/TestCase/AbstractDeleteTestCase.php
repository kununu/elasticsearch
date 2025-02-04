<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\DeleteException;
use Kununu\Elasticsearch\Exception\DocumentNotFoundException;
use Kununu\Elasticsearch\Repository\AbstractRepository;

abstract class AbstractDeleteTestCase extends AbstractRepositoryTestCase
{
    public function testDelete(): void
    {
        $this->client
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->delete(self::ID);
    }

    public function testDeleteWithForcedRefresh(): void
    {
        $this->client
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index'   => self::INDEX['write'],
                'id'      => self::ID,
                'refresh' => true,
            ]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepositoryWithForceRefresh()->delete(self::ID);
    }

    public function testDeleteFails(): void
    {
        $this->client
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->delete(self::ID);
        } catch (DeleteException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals(self::ID, $e->getDocumentId());
        }
    }

    public function testDeleteFailsBecauseDocumentNotFound(): void
    {
        $this->client
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ])
            ->willThrowException($this->createMissingException(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::never())
            ->method('error');

        try {
            $this->getRepository()->delete(self::ID);
        } catch (DocumentNotFoundException $e) {
            self::assertEquals(
                $this->formatMessage(sprintf('No document found with id %s', self::ID)),
                $e->getMessage()
            );
            self::assertEquals(0, $e->getCode());
            self::assertEquals(self::ID, $e->getDocumentId());
        }
    }

    public function testPostDeleteIsCalled(): void
    {
        $this->client
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repository = new class($this->client, ['index_write' => self::INDEX['write']]) extends AbstractRepository {
            protected function postDelete(string $id): void
            {
                AbstractRepositoryTestCase::assertEquals(AbstractRepositoryTestCase::ID, $id);
            }
        };

        $repository->delete(self::ID);
    }
}
