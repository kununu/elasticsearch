<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\IndexManagement;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\IndicesNamespace;
use Kununu\Elasticsearch\Exception\IndexManagementException;
use Kununu\Elasticsearch\IndexManagement\IndexManager;
use Kununu\Elasticsearch\IndexManagement\IndexManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;

/**
 * @group unit
 */
class IndexManagerTest extends MockeryTestCase
{
    protected const INDEX = 'my_index';
    protected const TYPE = '_doc';
    protected const ALIAS = 'my_alias';
    protected const MAPPING = [
        'properties' => [
            'field_a' => ['type' => 'text'],
        ],
    ];
    protected const SCHEMA = [
        self::TYPE => self::MAPPING,
    ];

    /** @var \Elasticsearch\Client|\Mockery\MockInterface */
    protected $clientMock;

    /**
     * @var \Elasticsearch\Namespaces\IndicesNamespace|\Mockery\MockInterface
     */
    protected $indicesMock;

    /**
     * @var \Psr\Log\LoggerInterface|\Mockery\MockInterface
     */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->clientMock = Mockery::mock(Client::class);
        $this->indicesMock = Mockery::mock(IndicesNamespace::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
    }

    /**
     * @return \Kununu\Elasticsearch\IndexManagement\IndexManagerInterface
     */
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

    /**
     * @return array
     */
    public function notAcknowledgedResponseData(): array
    {
        return [
            'acknowledged false' => [
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
                    'name' => self::ALIAS,
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

    /**
     * @dataProvider notAcknowledgedResponseData
     *
     * @param array $response
     */
    public function testAddAliasFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('putAlias')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'name' => self::ALIAS,
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
                    'name' => self::ALIAS,
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

    /**
     * @dataProvider notAcknowledgedResponseData
     *
     * @param array $response
     */
    public function testRemoveAliasFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('deleteAlias')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'name' => self::ALIAS,
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

    /**
     * @dataProvider notAcknowledgedResponseData
     *
     * @param array $response
     */
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
                    'message' => 'Operation not acknowledged',
                    'from_index' => $fromIndex,
                    'to_index' => $toIndex,
                    'alias' => self::ALIAS,
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

    /**
     * @return array
     */
    public function createIndexData(): array
    {
        $settings = ['index' => ['number_of_shards' => 5, 'number_of_replicas' => 1]];

        return [
            'completely blank' => [
                'input' => [
                    self::INDEX,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                ],
            ],
            'no aliases, no settings' => [
                'input' => [
                    self::INDEX,
                    self::SCHEMA,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body' => ['mappings' => self::SCHEMA],
                ],
            ],
            'with alias, no settings' => [
                'input' => [
                    self::INDEX,
                    self::SCHEMA,
                    [self::ALIAS],
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body' => ['mappings' => self::SCHEMA, 'aliases' => [self::ALIAS => new \stdClass()]],
                ],
            ],
            'no aliases, with settings' => [
                'input' => [
                    self::INDEX,
                    self::SCHEMA,
                    [],
                    $settings,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body' => ['mappings' => self::SCHEMA, 'settings' => $settings],
                ],
            ],
            'with alias and settings' => [
                'input' => [
                    self::INDEX,
                    self::SCHEMA,
                    [self::ALIAS],
                    $settings,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body' => [
                        'mappings' => self::SCHEMA,
                        'aliases' => [self::ALIAS => new \stdClass()],
                        'settings' => $settings,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider  createIndexData
     *
     * @param array $input
     * @param array $expectedRequestBody
     */
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

    /**
     * @dataProvider notAcknowledgedResponseData
     *
     * @param array $response
     */
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
                    'message' => 'Operation not acknowledged',
                    'index' => self::INDEX,
                    'aliases' => [],
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

    /**
     * @dataProvider notAcknowledgedResponseData
     *
     * @param array $response
     */
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
                    'index' => self::INDEX,
                    'body' => self::MAPPING,
                    'type' => self::TYPE,
                    'extra_param' => true,
                ]
            )
            ->andReturn(['acknowledged' => true]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->putMapping(self::INDEX, self::TYPE, self::MAPPING, ['extra_param' => true]);
    }

    /**
     * @dataProvider notAcknowledgedResponseData
     *
     * @param array $response
     */
    public function testPutMappingFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->shouldReceive('putMapping')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'body' => self::MAPPING,
                    'type' => self::TYPE,
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
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'mapping' => self::MAPPING,
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage('Elasticsearch exception: Operation not acknowledged');

        $this->getManager()->putMapping(self::INDEX, self::TYPE, self::MAPPING);
    }

    /**
     * @return array
     */
    public function indicesByAliasData(): array
    {
        return [
            'no indices mapped to alias' => [
                'es_response' => [],
                'expected_result' => [],
            ],
            'one index mapped to alias' => [
                'es_response' => [self::INDEX => ['foo' => 'bar']],
                'expected_result' => [self::INDEX],
            ],
            'multiple indices mapped to alias' => [
                'es_response' => [self::INDEX => ['foo' => 'bar'], 'another_index' => []],
                'expected_result' => [self::INDEX, 'another_index'],
            ],
        ];
    }

    /**
     * @dataProvider indicesByAliasData
     *
     * @param array $esResponse
     * @param array $expectedResult
     */
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
            ->andThrow(new \Exception('something happened'));

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Unable to get indices by alias',
                [
                    'message' => 'something happened',
                    'alias' => self::ALIAS,
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

    /**
     * @return array
     */
    public function indexAliasMappingData(): array
    {
        return [
            'no indices' => [
                'es_response' => [],
                'expected_result' => [],
            ],
            'one index without alias' => [
                'es_response' => [self::INDEX => ['aliases' => []]],
                'expected_result' => [self::INDEX => []],
            ],
            'one index with one alias' => [
                'es_response' => [self::INDEX => ['aliases' => [self::ALIAS => ['foo' => 'bar']]]],
                'expected_result' => [self::INDEX => [self::ALIAS]],
            ],
            'one index with multiple aliases' => [
                'es_response' => [self::INDEX => ['aliases' => [self::ALIAS => ['foo' => 'bar'], 'other_alias' => []]]],
                'expected_result' => [self::INDEX => [self::ALIAS, 'other_alias']],
            ],
            'multiple indices without alias' => [
                'es_response' => [self::INDEX => ['aliases' => []], 'another_index' => ['aliases' => []]],
                'expected_result' => [self::INDEX => [], 'another_index' => []],
            ],
            'multiple indices with one alias' => [
                'es_response' => [
                    self::INDEX => ['aliases' => [self::ALIAS => ['foo' => 'bar']]],
                    'another_index' => ['aliases' => ['fancy_index_alias' => []]],
                ],
                'expected_result' => [self::INDEX => [self::ALIAS], 'another_index' => ['fancy_index_alias']],
            ],
            'multiple indices with multiple aliases' => [
                'es_response' => [
                    self::INDEX => ['aliases' => [self::ALIAS => ['foo' => 'bar'], 'other_alias' => []]],
                    'other_index' => ['aliases' => ['my_special_alias' => []]],
                ],
                'expected_result' => [
                    self::INDEX => [self::ALIAS, 'other_alias'],
                    'other_index' => ['my_special_alias'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider indexAliasMappingData
     *
     * @param array $esResponse
     * @param array $expectedResult
     */
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
            ->andThrow(new \Exception('something happened'));

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
                    'refresh' => true,
                    'slices' => 'auto',
                    'wait_for_completion' => true,
                    'body' => [
                        'conflicts' => 'proceed',
                        'source' => ['index' => self::INDEX],
                        'dest' => ['index' => self::INDEX . '_v2'],
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
            ->andThrow(new \Exception('something happened'));

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'Elasticsearch exception: Unable to reindex',
                [
                    'message' => 'something happened',
                    'source' => self::INDEX,
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
                'body' => [
                    'index' => [
                        'refresh_interval' => '3m',
                        'number_of_replicas' => 5,
                    ],
                ],
            ])
            ->andReturn(['acknowledged' => true]);

        $this->getManager()->putSettings(
            'test',
            [
                'refresh_interval' => '3m',
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
        $this->expectExceptionMessage('Elasticsearch exception: Allowed settings are [refresh_interval, number_of_replicas]. Other settings are not allowed.');

        $this->getManager()->putSettings(
            'test',
            [
                'number_of_shards' => 1
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
        $this->expectExceptionMessage('Elasticsearch exception: Allowed settings are [refresh_interval, number_of_replicas]. Other settings are not allowed.');

        $this->getManager()->putSettings(
            'test',
            [
                'number_of_replicas' => 2,
                'number_of_shards' => 1
            ]
        );
    }
}
