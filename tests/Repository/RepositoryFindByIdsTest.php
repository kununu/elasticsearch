<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;

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

    /** @dataProvider findByIdsResultDataProvider */
    public function testFindByIds(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('critical');

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $this->assertEquals($endResult, $this->getRepository()->findByIds([self::ID, self::ID_2]));
    }

    /** @dataProvider findByIdsResultDataProvider */
    public function testFindByIdsWithSourceField(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('critical');

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $this->assertEquals($endResult, $this->getRepository()->findByIds([self::ID, self::ID_2], ['foo', 'foo2']));
    }

    /** @dataProvider findByIdsResultWithEntitiesDataProvider */
    public function testFindByIdWithEntityClass(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('critical');

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $results = $this
            ->getRepository(['entity_class' => $this->getEntityClass()])
            ->findByIds([self::ID, self::ID_2]);

        $this->assertEquals(array_values($endResult), $results);
        if (!empty($endResult)) {
            foreach ($endResult as $id => $result) {
                $this->assertEquals(
                    ['_index' => self::INDEX['read'], '_id' => $id, '_version' => 1, 'found' => true],
                    $result->_meta
                );
            }
        }
    }

    /** @dataProvider findByIdsResultWithEntitiesDataProvider */
    public function testFindByIdsWithEntityFactory(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('critical');

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $results = $this
            ->getRepository(['entity_factory' => $this->getEntityFactory()])
            ->findByIds([self::ID, self::ID_2]);

        $this->assertEquals(array_values($endResult), $results);
        if (!empty($endResult)) {
            foreach ($endResult as $id => $result) {
                $this->assertEquals(
                    ['_index' => self::INDEX['read'], '_id' => $id, '_version' => 1, 'found' => true],
                    $result->_meta
                );
            }
        }
    }

    public function testFindByIdsWithoutIds(): void
    {
        $this->assertEmpty($this->getRepository()->findByIds([]));
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
            ->expects($this->once())
            ->method('mget')
            ->with($body)
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects($this->once())
            ->method('critical')
            ->with(
                'Elasticsearch request error',
                ['request' => json_encode($body)]
            );

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                self::ERROR_PREFIX . self::ERROR_MESSAGE
            );

        try {
            $this->getRepository()->findByIds([self::ID, self::ID_2]);
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
    }
}
