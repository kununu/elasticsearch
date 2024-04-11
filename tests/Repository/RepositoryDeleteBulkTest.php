<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\BulkException;
use Kununu\Elasticsearch\Repository\Repository;

final class RepositoryDeleteBulkTest extends AbstractRepositoryTestCase
{
    public function testDeleteBulk(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('bulk')
            ->with([
                'index' => self::INDEX['write'],
                'body'  => [
                    [
                        'delete' => [
                            '_id' => self::ID,
                        ],
                    ],
                    [
                        'delete' => [
                            '_id' => self::ID_2,
                        ],
                    ],
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkWithForcedRefresh(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('bulk')
            ->with([
                'index'   => self::INDEX['write'],
                'refresh' => true,
                'body'    => [
                    [
                        'delete' => [
                            '_id' => self::ID,
                        ],
                    ],
                    [
                        'delete' => [
                            '_id' => self::ID_2,
                        ],
                    ],
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['force_refresh_on_write' => true])->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkFails(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('bulk')
            ->with([
                'index' => self::INDEX['write'],
                'body'  => $operations = [
                    [
                        'delete' => [
                            '_id' => self::ID,
                        ],
                    ],
                    [
                        'delete' => [
                            '_id' => self::ID_2,
                        ],
                    ],
                ],
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->deleteBulk(self::ID, self::ID_2);
        } catch (BulkException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals($operations, $e->getOperations());
        }
    }

    public function testPostDeleteBulkIsCalled(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('bulk')
            ->with([
                'index' => self::INDEX['write'],
                'body'  => [
                    [
                        'delete' => [
                            '_id' => self::ID,
                        ],
                    ],
                    [
                        'delete' => [
                            '_id' => self::ID_2,
                        ],
                    ],
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $manager = new class($this->clientMock, ['index_write' => self::INDEX['write']]) extends Repository {
            protected function postDeleteBulk(string ...$ids): void
            {
                AbstractRepositoryTestCase::assertCount(2, $ids);
                AbstractRepositoryTestCase::assertEquals(AbstractRepositoryTestCase::ID, $ids[0]);
                AbstractRepositoryTestCase::assertEquals(AbstractRepositoryTestCase::ID_2, $ids[1]);
            }
        };

        $manager->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkWithoutIds(): void
    {
        $this->clientMock
            ->expects(self::never())
            ->method('bulk');

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->deleteBulk();
    }
}
