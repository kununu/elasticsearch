<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\IndexManagement;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\IndicesNamespace;
use Exception;
use Kununu\Elasticsearch\Exception\IndexManagementException;
use Kununu\Elasticsearch\Exception\MoreThanOneIndexForAliasException;
use Kununu\Elasticsearch\Exception\NoIndexForAliasException;
use Kununu\Elasticsearch\IndexManagement\IndexManager;
use Kununu\Elasticsearch\IndexManagement\IndexManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

final class IndexManagerTest extends TestCase
{
    private const INDEX = 'my_index';
    private const ALIAS = 'my_alias';
    private const MAPPING = [
        'properties' => [
            'field_a' => ['type' => 'text'],
        ],
    ];

    private MockObject&Client $clientMock;
    private MockObject&IndicesNamespace $indicesMock;
    private MockObject&LoggerInterface $loggerMock;

    public static function notAcknowledgedResponseDataProvider(): array
    {
        return [
            'acknowledged_false'         => [
                'response' => ['acknowledged' => false],
            ],
            'acknowledged_field_missing' => [
                'response' => [],
            ],
        ];
    }

    public function testAddAlias(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('putAlias')
            ->with(
                [
                    'index' => self::INDEX,
                    'name'  => self::ALIAS,
                ]
            )
            ->willReturn(['acknowledged' => true]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getManager()->addAlias(
            self::INDEX,
            self::ALIAS
        );
    }

    #[DataProvider('notAcknowledgedResponseDataProvider')]
    public function testAddAliasFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('putAlias')
            ->with(
                [
                    'index' => self::INDEX,
                    'name'  => self::ALIAS,
                ]
            )
            ->willReturn($response);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            ->expects(self::once())
            ->method('deleteAlias')
            ->with(
                [
                    'index' => self::INDEX,
                    'name'  => self::ALIAS,
                ]
            )
            ->willReturn(['acknowledged' => true]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getManager()->removeAlias(
            self::INDEX,
            self::ALIAS
        );
    }

    #[DataProvider('notAcknowledgedResponseDataProvider')]
    public function testRemoveAliasFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('deleteAlias')
            ->with(
                [
                    'index' => self::INDEX,
                    'name'  => self::ALIAS,
                ]
            )
            ->willReturn($response);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            ->expects(self::once())
            ->method('updateAliases')
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
            ->willReturn(['acknowledged' => true]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getManager()->switchAlias(
            self::ALIAS,
            $fromIndex,
            $toIndex
        );
    }

    #[DataProvider('notAcknowledgedResponseDataProvider')]
    public function testSwitchAliasFails(array $response): void
    {
        $this->setUpIndexOperation();

        $fromIndex = 'from_ ' . self::INDEX;
        $toIndex = 'to_ ' . self::INDEX;

        $this->indicesMock
            ->expects(self::once())
            ->method('updateAliases')
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
            ->willReturn($response);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            'completely_blank'         => [
                'input'                 => [
                    self::INDEX,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                ],
            ],
            'no_aliases_no_settings'   => [
                'input'                 => [
                    self::INDEX,
                    self::MAPPING,
                ],
                'expected_request_body' => [
                    'index' => self::INDEX,
                    'body'  => ['mappings' => self::MAPPING],
                ],
            ],
            'with_alias_no_settings'   => [
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
            'no_aliases_with_settings' => [
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
            'with_alias_and_settings'  => [
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

    #[DataProvider('createIndexDataProvider')]
    public function testCreateIndex(array $input, array $expectedRequestBody): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('create')
            ->with($expectedRequestBody)
            ->willReturn(['acknowledged' => true]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getManager()->createIndex(...$input);
    }

    #[DataProvider('notAcknowledgedResponseDataProvider')]
    public function testCreateIndexFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('create')
            ->willReturn($response);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            ->expects(self::once())
            ->method('delete')
            ->with(
                ['index' => self::INDEX]
            )
            ->willReturn(['acknowledged' => true]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getManager()->deleteIndex(self::INDEX);
    }

    #[DataProvider('notAcknowledgedResponseDataProvider')]
    public function testDeleteIndexFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('delete')
            ->with(
                ['index' => self::INDEX]
            )
            ->willReturn($response);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            ->expects(self::once())
            ->method('putMapping')
            ->with(
                [
                    'index'       => self::INDEX,
                    'body'        => self::MAPPING,
                    'extra_param' => true,
                ]
            )
            ->willReturn(['acknowledged' => true]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getManager()->putMapping(self::INDEX, self::MAPPING, ['extra_param' => true]);
    }

    #[DataProvider('notAcknowledgedResponseDataProvider')]
    public function testPutMappingFails(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('putMapping')
            ->with(
                [
                    'index' => self::INDEX,
                    'body'  => self::MAPPING,
                ]
            )
            ->willReturn($response);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            'no_indices_mapped_to_alias'       => [
                'es_response'     => [],
                'expected_result' => [],
            ],
            'one_index_mapped_to_alias'        => [
                'es_response'     => [self::INDEX => ['foo' => 'bar']],
                'expected_result' => [self::INDEX],
            ],
            'multiple_indices_mapped_to_alias' => [
                'es_response'     => [self::INDEX => ['foo' => 'bar'], 'another_index' => []],
                'expected_result' => [self::INDEX, 'another_index'],
            ],
        ];
    }

    #[DataProvider('indicesByAliasDataProvider')]
    public function testGetIndicesByAlias(array $esResponse, array $expectedResult): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('getAlias')
            ->with(
                ['name' => self::ALIAS]
            )
            ->willReturn($esResponse);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals($expectedResult, $this->getManager()->getIndicesByAlias(self::ALIAS));
    }

    public function testGetIndicesByAliasFails(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('getAlias')
            ->willThrowException(new Exception('something happened'));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            ->expects(self::once())
            ->method('getAlias')
            ->willThrowException(new Missing404Exception());

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals([], $this->getManager()->getIndicesByAlias(self::ALIAS));
    }

    public static function indexAliasMappingDataProvider(): array
    {
        return [
            'no_indices'                             => [
                'es_response'     => [],
                'expected_result' => [],
            ],
            'one_index_without_alias'                => [
                'es_response'     => [self::INDEX => ['aliases' => []]],
                'expected_result' => [self::INDEX => []],
            ],
            'one_index_with_one_alias'               => [
                'es_response'     => [self::INDEX => ['aliases' => [self::ALIAS => ['foo' => 'bar']]]],
                'expected_result' => [self::INDEX => [self::ALIAS]],
            ],
            'one_index_with_multiple_aliases'        => [
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
            'multiple_indices_without_alias'         => [
                'es_response'     => [self::INDEX => ['aliases' => []], 'another_index' => ['aliases' => []]],
                'expected_result' => [self::INDEX => [], 'another_index' => []],
            ],
            'multiple_indices_with_one_alias'        => [
                'es_response'     => [
                    self::INDEX     => ['aliases' => [self::ALIAS => ['foo' => 'bar']]],
                    'another_index' => ['aliases' => ['fancy_index_alias' => []]],
                ],
                'expected_result' => [self::INDEX => [self::ALIAS], 'another_index' => ['fancy_index_alias']],
            ],
            'multiple_indices_with_multiple_aliases' => [
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

    #[DataProvider('indexAliasMappingDataProvider')]
    public function testGetIndicesAliasesMapping(array $esResponse, array $expectedResult): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('get')
            ->with(
                ['index' => '_all']
            )
            ->willReturn($esResponse);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals($expectedResult, $this->getManager()->getIndicesAliasesMapping());
    }

    public function testGetIndicesAliasesMappingCatches404(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('get')
            ->with(
                ['index' => '_all']
            )
            ->willThrowException(new Missing404Exception());

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals([], $this->getManager()->getIndicesAliasesMapping());
    }

    public function testGetIndicesAliasesMappingFails(): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('get')
            ->with(
                ['index' => '_all']
            )
            ->willThrowException(new Exception('something happened'));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            ->expects(self::once())
            ->method('reindex')
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
            ->willReturn(['acknowledged' => true]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getManager()->reindex(self::INDEX, self::INDEX . '_v2');
    }

    public function testReindexFails(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('reindex')
            ->willThrowException(new Exception('something happened'));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
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
            ->expects(self::once())
            ->method('indices')
            ->willReturn($this->indicesMock);

        $this->indicesMock
            ->expects(self::once())
            ->method('putSettings')
            ->with([
                'index' => 'test',
                'body'  => [
                    'index' => [
                        'refresh_interval'   => '3m',
                        'number_of_replicas' => 5,
                    ],
                ],
            ])
            ->willReturn(['acknowledged' => true]);

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
            ->expects(self::never())
            ->method('indices');

        $this->indicesMock
            ->expects(self::never())
            ->method('putSettings');

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
            ->expects(self::never())
            ->method('indices');

        $this->indicesMock
            ->expects(self::never())
            ->method('putSettings');

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

    #[DataProvider('getSingleIndexByAliasDataProvider')]
    public function testGetSingleIndexByAlias(array $esResponse, ?string $expectedResult): void
    {
        $this->setUpIndexOperation();

        $this->indicesMock
            ->expects(self::once())
            ->method('getAlias')
            ->with(['name' => self::ALIAS])
            ->willReturn($esResponse);

        if (empty($esResponse)) {
            $this->expectException(NoIndexForAliasException::class);
        }

        if (count($esResponse) > 1) {
            $this->expectException(MoreThanOneIndexForAliasException::class);
        }

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this->getManager()->getSingleIndexByAlias(self::ALIAS);

        self::assertEquals($expectedResult, $result);
    }

    public static function getSingleIndexByAliasDataProvider(): array
    {
        return [
            'no_indices_mapped_to_alias'       => [
                'es_response'     => [],
                'expected_result' => null,
            ],
            'one_index_mapped_to_alias'        => [
                'es_response'     => [self::INDEX => ['foo' => 'bar']],
                'expected_result' => self::INDEX,
            ],
            'multiple_indices_mapped_to_alias' => [
                'es_response'     => [self::INDEX => ['foo' => 'bar'], 'another_index' => []],
                'expected_result' => null,
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->indicesMock = $this->createMock(IndicesNamespace::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
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
            ->expects(self::once())
            ->method('indices')
            ->willReturn($this->indicesMock);
    }
}
