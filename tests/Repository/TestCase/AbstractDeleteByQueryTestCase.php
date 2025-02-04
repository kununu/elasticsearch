<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\WriteOperationException;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;

abstract class AbstractDeleteByQueryTestCase extends AbstractRepositoryTestCase
{
    public function testDeleteByQuery(): void
    {
        $expectedResult = ['some_fake_response' => 'deletion was successful'];

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepository()->deleteByQuery(Query::create(Filter::create('foo', 'bar')));

        self::assertEquals($expectedResult, $result);
    }

    public function testDeleteByQueryWithForcedRefresh(): void
    {
        $expectedResult = ['some_fake_response' => 'deletion was successful'];

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepositoryWithForceRefresh()->deleteByQuery(Query::create(Filter::create('foo', 'bar')));

        self::assertEquals($expectedResult, $result);
    }

    public function testDeleteByQueryWithProceedOnConflicts(): void
    {
        $expectedResult = ['some_fake_es_response' => 'deletion was successful'];

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepository()->deleteByQuery(Query::create(Filter::create('foo', 'bar')), true);

        self::assertEquals($expectedResult, $result);
    }

    public function testDeleteByQueryFails(): void
    {
        $this->client
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

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->deleteByQuery(Query::create(Filter::create('foo', 'bar')));
        } catch (WriteOperationException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
        }
    }
}
