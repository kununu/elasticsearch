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
            ->shouldReceive('deleteByQuery')
            ->once()
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
            ->andReturn($expectedResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository()->deleteByQuery(
            Query::create(
                Filter::create('foo', 'bar')
            )
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testDeleteByQueryWithForcedRefresh(): void
    {
        $expectedResult = ['some_fake_es_response' => 'deletion was successful'];

        $this->clientMock
            ->shouldReceive('deleteByQuery')
            ->once()
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
            ->andReturn($expectedResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository(['force_refresh_on_write' => true])->deleteByQuery(
            Query::create(
                Filter::create('foo', 'bar')
            )
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testDeleteByQueryWithProceedOnConflicts(): void
    {
        $expectedResult = ['some_fake_es_response' => 'deletion was successful'];

        $this->clientMock
            ->shouldReceive('deleteByQuery')
            ->once()
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
            ->andReturn($expectedResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository()->deleteByQuery(
            Query::create(
                Filter::create('foo', 'bar')
            ),
            true
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testDeleteByQueryFails(): void
    {
        $this->clientMock
            ->shouldReceive('deleteByQuery')
            ->once()
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
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->deleteByQuery(
                Query::create(
                    Filter::create('foo', 'bar')
                )
            );
        } catch (WriteOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
        }
    }
}
