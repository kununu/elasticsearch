<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\IndexManagement;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\IndicesNamespace;
use Exception;
use Kununu\Elasticsearch\Exception\IndexManagementException;
use Kununu\Elasticsearch\IndexManagement\IndexManager;
use Kununu\Elasticsearch\IndexManagement\IndexManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use stdClass;

final class IndexManagerTest extends MockeryTestCase
{
    protected const INDEX = 'my_index';
    protected const ALIAS = 'my_alias';
    protected const MAPPING = [
        'properties' => [
            'field_a' => ['type' => 'text'],
        ],
    ];

    protected MockInterface|Client $clientMock;
    protected MockInterface|IndicesNamespace $indicesMock;
    protected MockInterface|LoggerInterface $loggerMock;

    public static function notAcknowledgedResponseDataProvider(): array
    {
        return [
            'acknowledged false'         => [
                'response' => ['acknowledged' => false],
            ],
            'acknowledged field missing' => [
                'response' => [],
            ],
        ];
    }

    public function testAddAlias(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('putAlias')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'name'  => self::ALIAS,
                ]
            )
            ->andReturn(['acknowledged' => true]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->addAlias(
            self::INDEX,
            self::ALIAS
        );
    }

    /** @dataProvider notAcknowledgedResponseDataProvider */
    public function testAddAliasFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('putAlias')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'name'  => self::ALIAS,
                ]
            )
            ->andReturn($response);

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Could not add alias for index',
                ['message' => 'Operation not acknowledged', 'index' => self::INDEX, 'alias' => self::ALIAS]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: Operation not acknowledged');

        $this->getManager()->addAlias(
            self::INDEX,
            self::ALIAS
        );
    }

    public function testRemoveAlias(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('deleteAlias')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'name'  => self::ALIAS,
                ]
            )
            ->andReturn(['acknowledged' => true]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->removeAlias(
            self::INDEX,
            self::ALIAS
        );
    }

    /** @dataProvider notAcknowledgedResponseDataProvider */
    public function testRemoveAliasFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('deleteAlias')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'name'  => self::ALIAS,
                ]
            )
            ->andReturn($response);

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Could not remove alias for index',
                ['message' => 'Operation not acknowledged', 'index' => self::INDEX, 'alias' => self::ALIAS]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: Operation not acknowledged');

        $this->getManager()->removeAlias(
            self::INDEX,
            self::ALIAS
        );
    }

    public function testSwitchAlias(): void
    {
        $this->setUpIndexOperation();

        $fromIndex = 'from_ ' . self::INDEX;
        $toIndex = 'to_ ' . self::INDEX;

        $this->indicesMock
            ->shouldReceive('updateAliases')
            ->once()
            ->with(
                [
                    'body' => [
                        'actions' => [
                            ['remove' => ['index' => $fromIndex, 'alias' => self::ALIAS]],
                            ['add' => ['index' => $toIndex, 'alias' => self::ALIAS]],
                        ],
                    ],
                ]
            )
            ->andReturn(['acknowledged' => true]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->switchAlias(
            self::ALIAS,
            $fromIndex,
            $toIndex
        );
    }

    /** @dataProvider notAcknowledgedResponseDataProvider */
    public function testSwitchAliasFails(array $response): void
    {
        $this->setUpIndexOperation();

        $fromIndex = 'from_ ' . self::INDEX;
        $toIndex = 'to_ ' . self::INDEX;

        $this->indicesMock
            ->shouldReceive('updateAliases')
            ->once()
            ->with(
                [
                    'body' => [
                        'actions' => [
                            ['remove' => ['index' => $fromIndex, 'alias' => self::ALIAS]],
                            ['add' => ['index' => $toIndex, 'alias' => self::ALIAS]],
                        ],
                    ],
                ]
            )
            ->andReturn($response);

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Could not switch alias for index',
                [
                    'message'    => 'Operation not acknowledged',
                    'from_index' => $fromIndex,
                    'to_index'   => $toIndex,
                    'alias'      => self::ALIAS,
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: Operation not acknowledged');

        $this->getManager()->switchAlias(
            self::ALIAS,
            $fromIndex,
            $toIndex
        );
    }

    public static function createIndexDataProvider(): array
    {
        $settings = ['index' => ['number_of_shards' => 5, 'number_of_replicas' => 1]];

        return [
            'completely blank'          => [
                'input'                 => [
                    self::INDEX,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                ],
            ],
            'no aliases, no settings'   => [
                'input'                 => [
                    self::INDEX,
                    self::MAPPING,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body'  => ['mappings' => self::MAPPING],
                ],
            ],
            'with alias, no settings'   => [
                'input'                 => [
                    self::INDEX,
                    self::MAPPING,
                    [self::ALIAS],
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body'  => ['mappings' => self::MAPPING, 'aliases' => [self::ALIAS => new stdClass()]],
                ],
            ],
            'no aliases, with settings' => [
                'input'                 => [
                    self::INDEX,
                    self::MAPPING,
                    [],
                    $settings,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body'  => ['mappings' => self::MAPPING, 'settings' => $settings],
                ],
            ],
            'with alias and settings'   => [
                'input'                 => [
                    self::INDEX,
                    self::MAPPING,
                    [self::ALIAS],
                    $settings,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body'  => [
                        'mappings' => self::MAPPING,
                        'aliases'  => [self::ALIAS => new stdClass()],
                        'settings' => $settings,
                    ],
                ],
            ],
        ];
    }

    /** @dataProvider createIndexDataProvider */
    public function testCreateIndex(array $input, array $expectedRequestBody): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('create')
            ->once()
            ->with($expectedRequestBody)
            ->andReturn(['acknowledged' => true]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->createIndex(...$input);
    }

    /** @dataProvider notAcknowledgedResponseDataProvider */
    public function testCreateIndexFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($response);

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Could not create index',
                [
                    'message'  => 'Operation not acknowledged',
                    'index'    => self::INDEX,
                    'aliases'  => [],
                    'settings' => [],
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: Operation not acknowledged');

        $this->getManager()->createIndex(
            self::INDEX,
            []
        );
    }

    public function testDeleteIndex(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('delete')
            ->once()
            ->with(
                ['index' => self::INDEX]
            )
            ->andReturn(['acknowledged' => true]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->deleteIndex(self::INDEX);
    }

    /** @dataProvider notAcknowledgedResponseDataProvider */
    public function testDeleteIndexFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('delete')
            ->once()
            ->with(
                ['index' => self::INDEX]
            )
            ->andReturn($response);

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Could not delete index',
                ['message' => 'Operation not acknowledged', 'index' => self::INDEX]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: Operation not acknowledged');

        $this->getManager()->deleteIndex(self::INDEX);
    }

    public function testPutMapping(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('putMapping')
            ->once()
            ->with(
                [
                    'index'       => self::INDEX,
                    'body'        => self::MAPPING,
                    'extra_param' => true,
                ]
            )
            ->andReturn(['acknowledged' => true]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->putMapping(self::INDEX, self::MAPPING, ['extra_param' => true]);
    }

    /** @dataProvider notAcknowledgedResponseDataProvider */
    public function testPutMappingFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('putMapping')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'body'  => self::MAPPING,
                ]
            )
            ->andReturn($response);

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Could not put mapping',
                [
                    'message' => 'Operation not acknowledged',
                    'index'   => self::INDEX,
                    'mapping' => self::MAPPING,
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: Operation not acknowledged');

        $this->getManager()->putMapping(self::INDEX, self::MAPPING);
    }

    public static function indicesByAliasDataProvider(): array
    {
        return [
            'no indices mapped to alias'       => [
                'es_response'     => [],
                'expected_result' => [],
            ],
            'one index mapped to alias'        => [
                'es_response'     => [self::INDEX => ['foo' => 'bar']],
                'expected_result' => [self::INDEX],
            ],
            'multiple indices mapped to alias' => [
                'es_response'     => [self::INDEX => ['foo' => 'bar'], 'another_index' => []],
                'expected_result' => [self::INDEX, 'another_index'],
            ],
        ];
    }

    /** @dataProvider indicesByAliasDataProvider */
    public function testGetIndicesByAlias(array $esResponse, array $expectedResult): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('getAlias')
            ->once()
            ->with(
                ['name' => self::ALIAS]
            )
            ->andReturn($esResponse);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($expectedResult, $this->getManager()->getIndicesByAlias(self::ALIAS));
    }

    public function testGetIndicesByAliasFails(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('getAlias')
            ->once()
            ->andThrow(new Exception('something happened'));

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Unable to get indices by alias',
                [
                    'message' => 'something happened',
                    'alias'   => self::ALIAS,
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: something happened');

        $this->getManager()->getIndicesByAlias(self::ALIAS);
    }

    public function testGetIndicesByAliasCatches404(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('getAlias')
            ->once()
            ->andThrow(new Missing404Exception());

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals([], $this->getManager()->getIndicesByAlias(self::ALIAS));
    }

    public static function indexAliasMappingDataProvider(): array
    {
        return [
            'no indices'                             => [
                'es_response'     => [],
                'expected_result' => [],
            ],
            'one index without alias'                => [
                'es_response'     => [self::INDEX => ['aliases' => []]],
                'expected_result' => [self::INDEX => []],
            ],
            'one index with one alias'               => [
                'es_response'     => [self::INDEX => ['aliases' => [self::ALIAS => ['foo' => 'bar']]]],
                'expected_result' => [self::INDEX => [self::ALIAS]],
            ],
            'one index with multiple aliases'        => [
                'es_response'     => [
                    self::INDEX => [
                        'aliases' => [
                            self::ALIAS   => ['foo' => 'bar'],
                            'other_alias' => [],
                        ],
                    ],
                ],
                'expected_result' => [self::INDEX => [self::ALIAS, 'other_alias']],
            ],
            'multiple indices without alias'         => [
                'es_response'     => [self::INDEX => ['aliases' => []], 'another_index' => ['aliases' => []]],
                'expected_result' => [self::INDEX => [], 'another_index' => []],
            ],
            'multiple indices with one alias'        => [
                'es_response'     => [
                    self::INDEX     => ['aliases' => [self::ALIAS => ['foo' => 'bar']]],
                    'another_index' => ['aliases' => ['fancy_index_alias' => []]],
                ],
                'expected_result' => [self::INDEX => [self::ALIAS], 'another_index' => ['fancy_index_alias']],
            ],
            'multiple indices with multiple aliases' => [
                'es_response'     => [
                    self::INDEX   => ['aliases' => [self::ALIAS => ['foo' => 'bar'], 'other_alias' => []]],
                    'other_index' => ['aliases' => ['my_special_alias' => []]],
                ],
                'expected_result' => [
                    self::INDEX   => [self::ALIAS, 'other_alias'],
                    'other_index' => ['my_special_alias'],
                ],
            ],
        ];
    }

    /** @dataProvider indexAliasMappingDataProvider */
    public function testGetIndicesAliasesMapping(array $esResponse, array $expectedResult): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('get')
            ->once()
            ->with(
                ['index' => '_all']
            )
            ->andReturn($esResponse);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($expectedResult, $this->getManager()->getIndicesAliasesMapping());
    }

    public function testGetIndicesAliasesMappingCatches404(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('get')
            ->once()
            ->with(
                ['index' => '_all']
            )
            ->andThrow(new Missing404Exception());

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals([], $this->getManager()->getIndicesAliasesMapping());
    }

    public function testGetIndicesAliasesMappingFails(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('get')
            ->once()
            ->with(
                ['index' => '_all']
            )
            ->andThrow(new Exception('something happened'));

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Unable to get indices',
                [
                    'message' => 'something happened',
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: something happened');

        $this->getManager()->getIndicesAliasesMapping();
    }

    public function testReindex(): void
    {
        $this->clientMock
            ->shouldReceive('reindex')
            ->once()
            ->with(
                [
                    'refresh'             => true,
                    'slices'              => 'auto',
                    'wait_for_completion' => true,
                    'body'                => [
                        'conflicts' => 'proceed',
                        'source'    => ['index' => self::INDEX],
                        'dest'      => ['index' => self::INDEX . '_v2'],
                    ],
                ]
            )
            ->andReturn(['acknowledged' => true]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->reindex(self::INDEX, self::INDEX . '_v2');
    }

    public function testReindexFails(): void
    {
        $this->clientMock
            ->shouldReceive('reindex')
            ->once()
            ->andThrow(new Exception('something happened'));

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Unable to reindex',
                [
                    'message'     => 'something happened',
                    'source'      => self::INDEX,
                    'destination' => self::INDEX . '_v2',
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: something happened');

        $this->getManager()->reindex(self::INDEX, self::INDEX . '_v2');
    }

    public function testPutSettings(): void
    {
        $this->clientMock
            ->shouldReceive('indices')
            ->once()
            ->withNoArgs()
            ->andReturn($this->indicesMock);

        $this->indicesMock
            ->shouldReceive('putSettings')
            ->once()
            ->with([
                'index' => 'test',
                'body'  => [
                    'index' => [
                        'refresh_interval'   => '3m',
                        'number_of_replicas' => 5,
                    ],
                ],
            ])
            ->andReturn(['acknowledged' => true]);

        $this->getManager()->putSettings(
            'test',
            [
                'refresh_interval'   => '3m',
                'number_of_replicas' => 5,
            ]
        );
    }

    public function testDisallowedPutSettings(): void
    {
        $this->clientMock
            ->shouldReceive('indices')
            ->never();

        $this->indicesMock
            ->shouldReceive('putSettings')
            ->never();

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage(
            'Elasticsearch exception: Allowed settings are [refresh_interval, number_of_replicas]. Other settings are not allowed.'
        );

        $this->getManager()->putSettings(
            'test',
            [
                'number_of_shards' => 1,
            ]
        );
    }

    public function testAllowedAndDisallowedPutSettings(): void
    {
        $this->clientMock
            ->shouldReceive('indices')
            ->never();

        $this->indicesMock
            ->shouldReceive('putSettings')
            ->never();

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage(
            'Elasticsearch exception: Allowed settings are [refresh_interval, number_of_replicas]. Other settings are not allowed.'
        );

        $this->getManager()->putSettings(
            'test',
            [
                'number_of_replicas' => 2,
                'number_of_shards'   => 1,
            ]
        );
    }

    protected function setUp(): void
    {
        $this->clientMock = Mockery::mock(Client::class);
        $this->indicesMock = Mockery::mock(IndicesNamespace::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
    }

    private function getManager(): IndexManagerInterface
    {
        $manager = new IndexManager($this->clientMock);

        $manager->setLogger($this->loggerMock);

        return $manager;
    }

    private function setUpIndexOperation(): void
    {
        $this->clientMock
            ->shouldReceive('indices')
            ->once()
            ->andReturn($this->indicesMock);
    }
}
