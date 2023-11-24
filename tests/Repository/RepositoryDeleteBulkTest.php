<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\BulkException;
use Kununu\Elasticsearch\Repository\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryDeleteBulkTest extends AbstractRepositoryTestCase
{
    public function testDeleteBulk(): void
    {
        $this->clientMock
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('error');

        $this->getRepository()->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkWithForcedRefresh(): void
    {
        $this->clientMock
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('error');

        $this->getRepository(['force_refresh_on_write' => true])->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkFails(): void
    {
        $this->clientMock
            ->expects($this->once())
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
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->deleteBulk(self::ID, self::ID_2);
        } catch (BulkException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals($operations, $e->getOperations());
        }
    }

    public function testPostDeleteBulkIsCalled(): void
    {
        $this->clientMock
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('error');

        $manager = new class($this->clientMock, ['index_write' => self::INDEX['write']]) extends Repository {
            protected function postDeleteBulk(string ...$ids): void
            {
                TestCase::assertCount(2, $ids);
                TestCase::assertEquals(AbstractRepositoryTestCase::ID, $ids[0]);
                TestCase::assertEquals(AbstractRepositoryTestCase::ID_2, $ids[1]);
            }
        };

        $manager->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkWithoutIds(): void
    {
        $this->clientMock
            ->expects($this->never())
            ->method('bulk');

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $this->getRepository()->deleteBulk();
    }
}
