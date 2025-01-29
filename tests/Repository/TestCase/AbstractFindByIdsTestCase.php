<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Tests\Stub\PersistableEntityStub;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractFindByIdsTestCase extends AbstractRepositoryTestCase
{
    public static function findByIdsResultDataProvider(): array
    {
        return [
            'no_results'      => [
                'result'    => [
                    'docs' => [
                        [
                            '_index' => self::INDEX['read'],
                            '_id'    => self::ID,
                            'found'  => false,
                        ],
                        [
                            '_index' => self::INDEX['read'],
                            '_id'    => self::ID_2,
                            'found'  => false,
                        ],
                    ],
                ],
                'endResult' => [],
            ],
            'partial_results' => [
                'result'    => [
                    'docs' => [
                        [
                            '_index'   => self::INDEX['read'],
                            '_id'      => self::ID,
                            '_version' => 1,
                            'found'    => true,
                            '_source'  => [
                                'foo' => 'bar',
                            ],
                        ],
                        [
                            '_index' => self::INDEX['read'],
                            '_id'    => self::ID_2,
                            'found'  => false,
                        ],
                    ],
                ],
                'endResult' => [
                    [
                        '_index'   => self::INDEX['read'],
                        '_id'      => self::ID,
                        '_version' => 1,
                        'found'    => true,
                        '_source'  => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
            'all_results'     => [
                'result'    => [
                    'docs' => [
                        [
                            '_index'   => self::INDEX['read'],
                            '_id'      => self::ID,
                            '_version' => 1,
                            'found'    => true,
                            '_source'  => [
                                'foo' => 'bar',
                            ],
                        ],
                        [
                            '_index'   => self::INDEX['read'],
                            '_id'      => self::ID_2,
                            '_version' => 1,
                            'found'    => true,
                            '_source'  => [
                                'foo' => 'bar 2',
                            ],
                        ],
                    ],
                ],
                'endResult' => [
                    [
                        '_index'   => self::INDEX['read'],
                        '_id'      => self::ID,
                        'found'    => true,
                        '_version' => 1,
                        '_source'  => [
                            'foo' => 'bar',
                        ],
                    ],
                    [
                        '_index'   => self::INDEX['read'],
                        '_id'      => self::ID_2,
                        '_version' => 1,
                        'found'    => true,
                        '_source'  => [
                            'foo' => 'bar 2',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function findByIdsResultWithEntitiesDataProvider(): array
    {
        return array_map(
            function(array $variables): array {
                $variables['endResult'] = [];
                foreach ($variables['result']['docs'] as $docs) {
                    if ($docs['found']) {
                        $entity = new PersistableEntityStub();
                        foreach ($docs['_source'] as $key => $value) {
                            $entity->$key = $value;
                        }
                        $entity->_meta = [
                            '_index'   => $docs['_index'],
                            '_id'      => $docs['_id'],
                            '_version' => $docs['_version'],
                            'found'    => $docs['found'],
                        ];

                        $variables['endResult'][$docs['_id']] = $entity;
                    }
                }

                return $variables;
            },
            self::findByIdsResultDataProvider()
        );
    }

    #[DataProvider('findByIdsResultDataProvider')]
    public function testFindByIds(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('mget')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => [
                    'docs' => [
                        [
                            '_id' => self::ID,
                        ],
                        [
                            '_id' => self::ID_2,
                        ],
                    ],
                ],
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('critical');

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository()->findByIds([self::ID, self::ID_2]));
    }

    #[DataProvider('findByIdsResultDataProvider')]
    public function testFindByIdsWithSourceField(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('mget')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => [
                    'docs' => [
                        [
                            '_id'     => self::ID,
                            '_source' => ['foo', 'foo2'],
                        ],
                        [
                            '_id'     => self::ID_2,
                            '_source' => ['foo', 'foo2'],
                        ],
                    ],
                ],
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('critical');

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository()->findByIds([self::ID, self::ID_2], ['foo', 'foo2']));
    }

    #[DataProvider('findByIdsResultWithEntitiesDataProvider')]
    public function testFindByIdWithEntityClass(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('mget')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => [
                    'docs' => [
                        [
                            '_id' => self::ID,
                        ],
                        [
                            '_id' => self::ID_2,
                        ],
                    ],
                ],
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('critical');

        $this->logger
            ->expects(self::never())
            ->method('error');

        $results = $this->getRepositoryWithEntityClass()->findByIds([self::ID, self::ID_2]);

        self::assertEquals(array_values($endResult), $results);
        if (!empty($endResult)) {
            foreach ($endResult as $id => $result) {
                self::assertEquals(
                    ['_index' => self::INDEX['read'], '_id' => $id, '_version' => 1, 'found' => true],
                    $result->_meta
                );
            }
        }
    }

    #[DataProvider('findByIdsResultWithEntitiesDataProvider')]
    public function testFindByIdsWithEntityFactory(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('mget')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => [
                    'docs' => [
                        [
                            '_id' => self::ID,
                        ],
                        [
                            '_id' => self::ID_2,
                        ],
                    ],
                ],
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('critical');

        $this->logger
            ->expects(self::never())
            ->method('error');

        $results = $this->getRepositoryWithEntityFactory()->findByIds([self::ID, self::ID_2]);

        self::assertEquals(array_values($endResult), $results);
        if (!empty($endResult)) {
            foreach ($endResult as $id => $result) {
                self::assertEquals(
                    ['_index' => self::INDEX['read'], '_id' => $id, '_version' => 1, 'found' => true],
                    $result->_meta
                );
            }
        }
    }

    public function testFindByIdsWithoutIds(): void
    {
        self::assertEmpty($this->getRepository()->findByIds([]));
    }

    public function testFindByIdsFails(): void
    {
        $body = [
            'index' => self::INDEX['read'],
            'body'  => [
                'docs' => [
                    [
                        '_id' => self::ID,
                    ],
                    [
                        '_id' => self::ID_2,
                    ],
                ],
            ],
        ];

        $this->client
            ->expects(self::once())
            ->method('mget')
            ->with($body)
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::once())
            ->method('critical')
            ->with(
                $this->formatMessage('Request error'),
                ['request' => json_encode($body)]
            );

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                $this->formatMessage(self::ERROR_MESSAGE)
            );

        try {
            $this->getRepository()->findByIds([self::ID, self::ID_2]);
        } catch (ReadOperationException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
