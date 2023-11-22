<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Exception;
use Kununu\Elasticsearch\Exception\DeleteException;
use Kununu\Elasticsearch\Exception\DocumentNotFoundException;
use Kununu\Elasticsearch\Repository\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryDeleteTest extends AbstractRepositoryTestCase
{
    public function testDelete(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository()->delete(self::ID);
    }

    public function testDeleteWithForcedRefresh(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with([
                'index'   => self::INDEX['write'],
                'id'      => self::ID,
                'refresh' => true,
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['force_refresh_on_write' => true])->delete(self::ID);
    }

    public function testDeleteFails(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ])
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->delete(self::ID);
        } catch (DeleteException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals(self::ID, $e->getDocumentId());
        }
    }

    public function testDeleteFailsBecauseDocumentNotFound(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ])
            ->andThrow(new Missing404Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldNotReceive('error');

        try {
            $this->getRepository()->delete(self::ID);
        } catch (DocumentNotFoundException $e) {
            $this->assertEquals(self::ERROR_PREFIX . 'No document found with id ' . self::ID, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals(self::ID, $e->getDocumentId());
        }
    }

    public function testPostDeleteIsCalled(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $manager = new class($this->clientMock, ['index_write' => self::INDEX['write']]) extends Repository {
            protected function postDelete(string $id): void
            {
                TestCase::assertEquals(AbstractRepositoryTestCase::ID, $id);
            }
        };

        $manager->delete(self::ID);
    }
}
