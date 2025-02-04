<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\BulkException;
use Kununu\Elasticsearch\Repository\AbstractRepository;

abstract class AbstractDeleteBulkTestCase extends AbstractRepositoryTestCase
{
    public function testDeleteBulk(): void
    {
        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkWithForcedRefresh(): void
    {
        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepositoryWithForceRefresh()->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkFails(): void
    {
        $this->client
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

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->deleteBulk(self::ID, self::ID_2);
        } catch (BulkException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals($operations, $e->getOperations());
        }
    }

    public function testPostDeleteBulkIsCalled(): void
    {
        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repository = new class($this->client, ['index_write' => self::INDEX['write']]) extends AbstractRepository {
            protected function postDeleteBulk(string ...$ids): void
            {
                AbstractRepositoryTestCase::assertCount(2, $ids);
                AbstractRepositoryTestCase::assertEquals(AbstractRepositoryTestCase::ID, $ids[0]);
                AbstractRepositoryTestCase::assertEquals(AbstractRepositoryTestCase::ID_2, $ids[1]);
            }
        };

        $repository->deleteBulk(self::ID, self::ID_2);
    }

    public function testDeleteBulkWithoutIds(): void
    {
        $this->client
            ->expects(self::never())
            ->method('bulk');

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->deleteBulk();
    }
}
