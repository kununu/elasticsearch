<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\WriteOperationException;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;

final class RepositoryDeleteByQueryTest extends AbstractRepositoryTestCase
{
    public function testDeleteByQuery(): void
    {
        $expectedResult = ['some_fake_es_response' => 'deletion was successful'];

        $this->clientMock
            ->expects(self::once())
            ->method('deleteByQuery')
            ->with([
                'index' => self::INDEX['write'],
                'body'  => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'term' => [
                                                'foo' => 'bar',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->willReturn($expectedResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepository()->deleteByQuery(
            Query::create(
                Filter::create('foo', 'bar')
            )
        );

        self::assertSame($expectedResult, $result);
    }

    public function testDeleteByQueryWithForcedRefresh(): void
    {
        $expectedResult = ['some_fake_es_response' => 'deletion was successful'];

        $this->clientMock
            ->expects(self::once())
            ->method('deleteByQuery')
            ->with([
                'index'   => self::INDEX['write'],
                'body'    => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'term' => [
                                                'foo' => 'bar',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'refresh' => true,
            ])
            ->willReturn($expectedResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepository(['force_refresh_on_write' => true])->deleteByQuery(
            Query::create(
                Filter::create('foo', 'bar')
            )
        );

        self::assertSame($expectedResult, $result);
    }

    public function testDeleteByQueryWithProceedOnConflicts(): void
    {
        $expectedResult = ['some_fake_es_response' => 'deletion was successful'];

        $this->clientMock
            ->expects(self::once())
            ->method('deleteByQuery')
            ->with([
                'index'     => self::INDEX['write'],
                'body'      => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'term' => [
                                                'foo' => 'bar',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'conflicts' => 'proceed',
            ])
            ->willReturn($expectedResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepository()->deleteByQuery(
            Query::create(
                Filter::create('foo', 'bar')
            ),
            true
        );

        self::assertSame($expectedResult, $result);
    }

    public function testDeleteByQueryFails(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('deleteByQuery')
            ->with([
                'index' => self::INDEX['write'],
                'body'  => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'term' => [
                                                'foo' => 'bar',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
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
            $this->getRepository()->deleteByQuery(
                Query::create(
                    Filter::create('foo', 'bar')
                )
            );
        } catch (WriteOperationException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
        }
    }
}
