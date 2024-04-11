<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use PHPUnit\Framework\Attributes\DataProvider;

final class RepositoryFindByIdsTest extends AbstractRepositoryTestCase
{
    public static function findByIdsResultDataProvider(): array
    {
        return [
            'no_results'      => [
                'es_result'  => [
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
                'end_result' => [],
            ],
            'partial_results' => [
                'es_result'  => [
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
                'end_result' => [
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
                'es_result'  => [
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
                'end_result' => [
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
                $variables['end_result'] = [];
                foreach ($variables['es_result']['docs'] as $docs) {
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

                        $variables['end_result'][$docs['_id']] = $entity;
                    }
                }

                return $variables;
            },
            self::findByIdsResultDataProvider()
        );
    }

    #[DataProvider('findByIdsResultDataProvider')]
    public function testFindByIds(array $esResult, mixed $endResult): void
    {
        $this->clientMock
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
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('critical');

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository()->findByIds([self::ID, self::ID_2]));
    }

    #[DataProvider('findByIdsResultDataProvider')]
    public function testFindByIdsWithSourceField(array $esResult, mixed $endResult): void
    {
        $this->clientMock
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
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('critical');

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository()->findByIds([self::ID, self::ID_2], ['foo', 'foo2']));
    }

    #[DataProvider('findByIdsResultWithEntitiesDataProvider')]
    public function testFindByIdWithEntityClass(array $esResult, mixed $endResult): void
    {
        $this->clientMock
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
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('critical');

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $results = $this
            ->getRepository(['entity_class' => PersistableEntityStub::class])
            ->findByIds([self::ID, self::ID_2]);

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
    public function testFindByIdsWithEntityFactory(array $esResult, mixed $endResult): void
    {
        $this->clientMock
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
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('critical');

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $results = $this
            ->getRepository(['entity_factory' => new EntityFactoryStub()])
            ->findByIds([self::ID, self::ID_2]);

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

        $this->clientMock
            ->expects(self::once())
            ->method('mget')
            ->with($body)
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with(
                'Elasticsearch request error',
                ['request' => json_encode($body)]
            );

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                self::ERROR_PREFIX . self::ERROR_MESSAGE
            );

        try {
            $this->getRepository()->findByIds([self::ID, self::ID_2]);
        } catch (ReadOperationException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
