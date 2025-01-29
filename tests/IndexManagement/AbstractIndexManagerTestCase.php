<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\IndexManagement;

use Elasticsearch\Client as ElasticClient;
use Elasticsearch\Namespaces\IndicesNamespace as ElasticIndicesNamespace;
use Exception;
use Kununu\Elasticsearch\Exception\IndexManagementException;
use Kununu\Elasticsearch\Exception\MoreThanOneIndexForAliasException;
use Kununu\Elasticsearch\Exception\NoIndexForAliasException;
use Kununu\Elasticsearch\IndexManagement\IndexManagerInterface;
use Kununu\Elasticsearch\Tests\AbstractClientTestCase;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\Namespaces\IndicesNamespace as OpenSearchIndicesNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use stdClass;
use Throwable;

abstract class AbstractIndexManagerTestCase extends AbstractClientTestCase
{
    private const string INDEX = 'my_index';
    private const string INDEX_V2 = 'my_index_v2';
    private const string FROM_INDEX = 'from_my_index';
    private const string TO_INDEX = 'to_my_index';
    private const string ALIAS = 'my_alias';
    private const array MAPPING = [
        'properties' => [
            'field_a' => ['type' => 'text'],
        ],
    ];
    private const string OPERATION_NOT_ACKNOWLEDGED = 'Operation not acknowledged';
    private const string ALLOWED_SETTINGS = <<<'TEXT'
Allowed settings are [refresh_interval, number_of_replicas]. Other settings are not allowed.
TEXT;
    private const string GENERAL_EXCEPTION = 'something happened';

    private (MockObject&ElasticIndicesNamespace)|(MockObject&OpenSearchIndicesNamespace) $indices;
    private IndexManagerInterface $manager;

    public static function dataProvider(): array
    {
        return [
            'acknowledged'                   => [
                ['acknowledged' => true],
                false,
            ],
            'not_acknowledged'               => [
                ['acknowledged' => false],
            ],
            'not_acknowledged_field_missing' => [
                'response' => [],
            ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testAddAlias(array $response, bool $expectError = true): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('putAlias')
            ->with([
                'index' => self::INDEX,
                'name'  => self::ALIAS,
            ])
            ->willReturn($response);

        if ($expectError) {
            $this->logger
                ->expects(self::once())
                ->method('error')
                ->with(
                    $this->formatMessage('Could not add alias for index'),
                    ['message' => self::OPERATION_NOT_ACKNOWLEDGED, 'index' => self::INDEX, 'alias' => self::ALIAS]
                );

            $this->expectException(IndexManagementException::class);
            $this->expectExceptionMessage($this->formatMessage(self::OPERATION_NOT_ACKNOWLEDGED));
        } else {
            $this->logger
                ->expects(self::never())
                ->method('error');
        }

        $this->manager->addAlias(self::INDEX, self::ALIAS);
    }

    #[DataProvider('dataProvider')]
    public function testRemoveAlias(array $response, bool $expectError = true): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('deleteAlias')
            ->with([
                'index' => self::INDEX,
                'name'  => self::ALIAS,
            ])
            ->willReturn($response);

        if ($expectError) {
            $this->logger
                ->expects(self::once())
                ->method('error')
                ->with(
                    $this->formatMessage('Could not remove alias for index'),
                    ['message' => self::OPERATION_NOT_ACKNOWLEDGED, 'index' => self::INDEX, 'alias' => self::ALIAS]
                );

            $this->expectException(IndexManagementException::class);
            $this->expectExceptionMessage($this->formatMessage(self::OPERATION_NOT_ACKNOWLEDGED));
        } else {
            $this->logger
                ->expects(self::never())
                ->method('error');
        }

        $this->manager->removeAlias(self::INDEX, self::ALIAS);
    }

    #[DataProvider('dataProvider')]
    public function testSwitchAlias(array $response, bool $expectError = true): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('updateAliases')
            ->with([
                'body' => [
                    'actions' => [
                        ['remove' => ['index' => self::FROM_INDEX, 'alias' => self::ALIAS]],
                        ['add' => ['index' => self::TO_INDEX, 'alias' => self::ALIAS]],
                    ],
                ],
            ])
            ->willReturn($response);

        if ($expectError) {
            $this->logger
                ->expects(self::once())
                ->method('error')
                ->with(
                    $this->formatMessage('Could not switch alias for index'),
                    [
                        'message'    => self::OPERATION_NOT_ACKNOWLEDGED,
                        'from_index' => self::FROM_INDEX,
                        'to_index'   => self::TO_INDEX,
                        'alias'      => self::ALIAS,
                    ]
                );

            $this->expectException(IndexManagementException::class);
            $this->expectExceptionMessage($this->formatMessage(self::OPERATION_NOT_ACKNOWLEDGED));
        } else {
            $this->logger
                ->expects(self::never())
                ->method('error');
        }

        $this->manager->switchAlias(self::ALIAS, self::FROM_INDEX, self::TO_INDEX);
    }

    #[DataProvider('createIndexDataProvider')]
    public function testCreateIndex(array $input, array $expectedRequestBody): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('create')
            ->with($expectedRequestBody)
            ->willReturn(['acknowledged' => true]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->manager->createIndex(...$input);
    }

    public static function createIndexDataProvider(): array
    {
        $settings = ['index' => ['number_of_shards' => 5, 'number_of_replicas' => 1]];

        return [
            'completely_blank'         => [
                'input'               => [
                    self::INDEX,
                ],
                'expectedRequestBody' => [
                    'index' => self::INDEX,
                ],
            ],
            'no_aliases_no_settings'   => [
                'input'               => [
                    self::INDEX,
                    self::MAPPING,
                ],
                'expectedRequestBody' => [
                    'index' => self::INDEX,
                    'body'  => ['mappings' => self::MAPPING],
                ],
            ],
            'with_alias_no_settings'   => [
                'input'               => [
                    self::INDEX,
                    self::MAPPING,
                    [self::ALIAS],
                ],
                'expectedRequestBody' => [
                    'index' => self::INDEX,
                    'body'  => ['mappings' => self::MAPPING, 'aliases' => [self::ALIAS => new stdClass()]],
                ],
            ],
            'no_aliases_with_settings' => [
                'input'               => [
                    self::INDEX,
                    self::MAPPING,
                    [],
                    $settings,
                ],
                'expectedRequestBody' => [
                    'index' => self::INDEX,
                    'body'  => ['mappings' => self::MAPPING, 'settings' => $settings],
                ],
            ],
            'with_alias_and_settings'  => [
                'input'               => [
                    self::INDEX,
                    self::MAPPING,
                    [self::ALIAS],
                    $settings,
                ],
                'expectedRequestBody' => [
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

    #[DataProvider('failCreateIndexDataProvider')]
    public function testFailCreateIndex(array $response): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('create')
            ->willReturn($response);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                $this->formatMessage('Could not create index'),
                [
                    'message'  => self::OPERATION_NOT_ACKNOWLEDGED,
                    'index'    => self::INDEX,
                    'aliases'  => [],
                    'settings' => [],
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage($this->formatMessage(self::OPERATION_NOT_ACKNOWLEDGED));

        $this->manager->createIndex(self::INDEX, []);
    }

    public static function failCreateIndexDataProvider(): array
    {
        return [
            'not_acknowledged'               => [
                ['acknowledged' => false],
            ],
            'not_acknowledged_field_missing' => [
                'response' => [],
            ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testDeleteIndex(array $response, bool $expectError = true): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('delete')
            ->with([
                'index' => self::INDEX,
            ])
            ->willReturn($response);

        if ($expectError) {
            $this->logger
                ->expects(self::once())
                ->method('error')
                ->with(
                    $this->formatMessage('Could not delete index'),
                    ['message' => self::OPERATION_NOT_ACKNOWLEDGED, 'index' => self::INDEX]
                );

            $this->expectException(IndexManagementException::class);
            $this->expectExceptionMessage($this->formatMessage(self::OPERATION_NOT_ACKNOWLEDGED));
        } else {
            $this->logger
                ->expects(self::never())
                ->method('error');
        }

        $this->manager->deleteIndex(self::INDEX);
    }

    #[DataProvider('dataProvider')]
    public function testPutMapping(array $response, bool $expectError = true): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('putMapping')
            ->with([
                'index'       => self::INDEX,
                'body'        => self::MAPPING,
                'extra_param' => true,
            ])
            ->willReturn($response);

        if ($expectError) {
            $this->logger
                ->expects(self::once())
                ->method('error')
                ->with(
                    $this->formatMessage('Could not put mapping'),
                    [
                        'message' => self::OPERATION_NOT_ACKNOWLEDGED,
                        'index'   => self::INDEX,
                        'mapping' => self::MAPPING,
                    ]
                );

            $this->expectException(IndexManagementException::class);
            $this->expectExceptionMessage($this->formatMessage(self::OPERATION_NOT_ACKNOWLEDGED));
        } else {
            $this->logger
                ->expects(self::never())
                ->method('error');
        }

        $this->manager->putMapping(self::INDEX, self::MAPPING, ['extra_param' => true]);
    }

    #[DataProvider('getIndicesByAliasDataProvider')]
    public function testGetIndicesByAlias(array $response, array $expectedResult): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('getAlias')
            ->with([
                'name' => self::ALIAS,
            ])
            ->willReturn($response);

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals($expectedResult, $this->manager->getIndicesByAlias(self::ALIAS));
    }

    public static function getIndicesByAliasDataProvider(): array
    {
        return [
            'no_indices_mapped_to_alias'       => [
                'response'       => [],
                'expectedResult' => [],
            ],
            'one_index_mapped_to_alias'        => [
                'response'       => [self::INDEX => ['foo' => 'bar']],
                'expectedResult' => [self::INDEX],
            ],
            'multiple_indices_mapped_to_alias' => [
                'response'       => [self::INDEX => ['foo' => 'bar'], 'another_index' => []],
                'expectedResult' => [self::INDEX, 'another_index'],
            ],
        ];
    }

    public function testFailGetIndicesByAlias(): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('getAlias')
            ->with([
                'name' => self::ALIAS,
            ])
            ->willThrowException(new Exception(self::GENERAL_EXCEPTION));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                $this->formatMessage('Unable to get indices by alias'),
                [
                    'message' => self::GENERAL_EXCEPTION,
                    'alias'   => self::ALIAS,
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage($this->formatMessage(self::GENERAL_EXCEPTION));

        $this->manager->getIndicesByAlias(self::ALIAS);
    }

    public function testMissingGetIndicesByAlias(): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('getAlias')
            ->with([
                'name' => self::ALIAS,
            ])
            ->willThrowException($this->createMissingException());

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals([], $this->manager->getIndicesByAlias(self::ALIAS));
    }

    #[DataProvider('getIndicesAliasesMappingDataProvider')]
    public function testGetIndicesAliasesMapping(array $response, array $expectedResult): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => '_all',
            ])
            ->willReturn($response);

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals($expectedResult, $this->manager->getIndicesAliasesMapping());
    }

    public static function getIndicesAliasesMappingDataProvider(): array
    {
        return [
            'no_indices'                             => [
                'response'       => [],
                'expectedResult' => [],
            ],
            'one_index_without_alias'                => [
                'response'       => [self::INDEX => ['aliases' => []]],
                'expectedResult' => [self::INDEX => []],
            ],
            'one_index_with_one_alias'               => [
                'response'       => [self::INDEX => ['aliases' => [self::ALIAS => ['foo' => 'bar']]]],
                'expectedResult' => [self::INDEX => [self::ALIAS]],
            ],
            'one_index_with_multiple_aliases'        => [
                'response'       => [
                    self::INDEX => [
                        'aliases' => [
                            self::ALIAS   => ['foo' => 'bar'],
                            'other_alias' => [],
                        ],
                    ],
                ],
                'expectedResult' => [self::INDEX => [self::ALIAS, 'other_alias']],
            ],
            'multiple_indices_without_alias'         => [
                'response'       => [self::INDEX => ['aliases' => []], 'another_index' => ['aliases' => []]],
                'expectedResult' => [self::INDEX => [], 'another_index' => []],
            ],
            'multiple_indices_with_one_alias'        => [
                'response'       => [
                    self::INDEX     => ['aliases' => [self::ALIAS => ['foo' => 'bar']]],
                    'another_index' => ['aliases' => ['fancy_index_alias' => []]],
                ],
                'expectedResult' => [self::INDEX => [self::ALIAS], 'another_index' => ['fancy_index_alias']],
            ],
            'multiple_indices_with_multiple_aliases' => [
                'response'       => [
                    self::INDEX   => ['aliases' => [self::ALIAS => ['foo' => 'bar'], 'other_alias' => []]],
                    'other_index' => ['aliases' => ['my_special_alias' => []]],
                ],
                'expectedResult' => [
                    self::INDEX   => [self::ALIAS, 'other_alias'],
                    'other_index' => ['my_special_alias'],
                ],
            ],
        ];
    }

    public function testFailGetIndicesAliasesMapping(): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => '_all',
            ])
            ->willThrowException(new Exception(self::GENERAL_EXCEPTION));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                $this->formatMessage('Unable to get indices'),
                [
                    'message' => self::GENERAL_EXCEPTION,
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage($this->formatMessage(self::GENERAL_EXCEPTION));

        $this->manager->getIndicesAliasesMapping();
    }

    public function testMissingGetIndicesAliasesMapping(): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => '_all',
            ])
            ->willThrowException($this->createMissingException());

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals([], $this->manager->getIndicesAliasesMapping());
    }

    public function testReindex(): void
    {
        $this->client
            ->expects(self::once())
            ->method('reindex')
            ->with([
                'refresh'             => true,
                'slices'              => 'auto',
                'wait_for_completion' => true,
                'body'                => [
                    'conflicts' => 'proceed',
                    'source'    => ['index' => self::INDEX],
                    'dest'      => ['index' => self::INDEX_V2],
                ],
            ])
            ->willReturn(['acknowledged' => true]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->manager->reindex(self::INDEX, self::INDEX_V2);
    }

    public function testFailReindex(): void
    {
        $this->client
            ->expects(self::once())
            ->method('reindex')
            ->with([
                'refresh'             => true,
                'slices'              => 'auto',
                'wait_for_completion' => true,
                'body'                => [
                    'conflicts' => 'proceed',
                    'source'    => ['index' => self::INDEX],
                    'dest'      => ['index' => self::INDEX_V2],
                ],
            ])
            ->willThrowException(new Exception(self::GENERAL_EXCEPTION));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                $this->formatMessage('Unable to reindex'),
                [
                    'message'     => self::GENERAL_EXCEPTION,
                    'source'      => self::INDEX,
                    'destination' => self::INDEX_V2,
                ]
            );

        $this->expectException(IndexManagementException::class);
        $this->expectExceptionMessage($this->formatMessage(self::GENERAL_EXCEPTION));

        $this->manager->reindex(self::INDEX, self::INDEX_V2);
    }

    #[DataProvider('putSettingsDataProvider')]
    public function testPutSettings(
        array $settings,
        array|Throwable $response,
        bool $expectError,
        bool $expectValidationError,
        string $expectedValidationMessage,
    ): void {
        $this->setUpIndexOperation(!$expectValidationError);

        $index = ['index' => $settings];
        $params = [
            'index' => self::INDEX,
            'body'  => $index,
        ];

        if ($expectError || $expectValidationError) {
            if ($expectValidationError) {
                $this->indices
                    ->expects(self::never())
                    ->method('putSettings');
            } else {
                $indicesInvoker = $this->indices
                    ->expects(self::once())
                    ->method('putSettings')
                    ->with($params);

                if (is_array($response)) {
                    $indicesInvoker->willReturn($response);
                } else {
                    $indicesInvoker->willThrowException($response);
                }

                $this->logger
                    ->expects(self::once())
                    ->method('error')
                    ->with(
                        $this->formatMessage('Unable to put settings'),
                        ['message' => $expectedValidationMessage, 'index' => self::INDEX, 'body' => $index]
                    );
            }

            $this->expectException(IndexManagementException::class);
            $this->expectExceptionMessage($this->formatMessage($expectedValidationMessage));
        } else {
            $this->indices
                ->expects(self::once())
                ->method('putSettings')
                ->with($params)
                ->willReturn($response);
        }

        $this->manager->putSettings(self::INDEX, $settings);
    }

    public static function putSettingsDataProvider(): array
    {
        return [
            'success'                         => [
                'settings'                  => [
                    'refresh_interval'   => '3m',
                    'number_of_replicas' => 5,
                ],
                'response'                  => ['acknowledged' => true],
                'expectError'               => false,
                'expectValidationError'     => false,
                'expectedValidationMessage' => '',
            ],
            'disallowed_settings'             => [
                'settings'                  => [
                    'number_of_shards' => 1,
                ],
                'response'                  => [],
                'expectError'               => true,
                'expectValidationError'     => true,
                'expectedValidationMessage' => self::ALLOWED_SETTINGS,
            ],
            'allowed_and_disallowed_settings' => [
                'settings'                  => [
                    'number_of_replicas' => 2,
                    'number_of_shards'   => 1,
                ],
                'response'                  => [],
                'expectError'               => true,
                'expectValidationError'     => true,
                'expectedValidationMessage' => self::ALLOWED_SETTINGS,
            ],
            'failure_not_acknowledge'         => [
                'settings'                  => [
                    'refresh_interval'   => '3m',
                    'number_of_replicas' => 5,
                ],
                'response'                  => ['acknowledged' => false],
                'expectError'               => true,
                'expectValidationError'     => false,
                'expectedValidationMessage' => self::OPERATION_NOT_ACKNOWLEDGED,
            ],
            'failure'                         => [
                'settings'                  => [
                    'refresh_interval'   => '3m',
                    'number_of_replicas' => 5,
                ],
                'response'                  => new Exception(self::GENERAL_EXCEPTION),
                'expectError'               => true,
                'expectValidationError'     => false,
                'expectedValidationMessage' => self::GENERAL_EXCEPTION,
            ],
        ];
    }

    #[DataProvider('getSingleIndexByAliasDataProvider')]
    public function testGetSingleIndexByAlias(array $response, ?string $expectedResult): void
    {
        $this->setUpIndexOperation();

        $this->indices
            ->expects(self::once())
            ->method('getAlias')
            ->with(['name' => self::ALIAS])
            ->willReturn($response);

        if (empty($response)) {
            $this->expectException(NoIndexForAliasException::class);
        }

        if (count($response) > 1) {
            $this->expectException(MoreThanOneIndexForAliasException::class);
        }

        $this->logger
            ->expects(self::never())
            ->method('error');

        $result = $this->manager->getSingleIndexByAlias(self::ALIAS);

        self::assertEquals($expectedResult, $result);
    }

    public static function getSingleIndexByAliasDataProvider(): array
    {
        return [
            'no_indices_mapped_to_alias'       => [
                'response'       => [],
                'expectedResult' => null,
            ],
            'one_index_mapped_to_alias'        => [
                'response'       => [self::INDEX => ['foo' => 'bar']],
                'expectedResult' => self::INDEX,
            ],
            'multiple_indices_mapped_to_alias' => [
                'response'       => [self::INDEX => ['foo' => 'bar'], 'another_index' => []],
                'expectedResult' => null,
            ],
        ];
    }

    abstract protected function createClient(): (MockObject&ElasticClient)|(MockObject&OpenSearchClient);

    abstract protected function createIndicesNamespace(
    ): (MockObject&ElasticIndicesNamespace)|(MockObject&OpenSearchIndicesNamespace);

    abstract protected function createManager(
        ElasticClient|OpenSearchClient $client,
        LoggerInterface $logger,
    ): IndexManagerInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indices = $this->createIndicesNamespace();
        $this->manager = $this->createManager($this->client, $this->logger);
    }

    private function setUpIndexOperation(bool $expectCall = true): void
    {
        $this->client
            ->expects($expectCall ? self::once() : self::never())
            ->method('indices')
            ->willReturn($this->indices);
    }
}
