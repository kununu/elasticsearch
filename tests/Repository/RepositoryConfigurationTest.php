<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Repository\EntityFactoryInterface;
use Kununu\Elasticsearch\Repository\EntitySerializerInterface;
use Kununu\Elasticsearch\Repository\OperationType;
use Kununu\Elasticsearch\Repository\PersistableEntityInterface;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

final class RepositoryConfigurationTest extends TestCase
{
    #[DataProvider('inflateConfigDataProvider')]
    public function testInflateConfig(array $input, string $expectedReadAlias, string $expectedWriteAlias): void
    {
        $config = self::build($input);

        self::assertEquals($expectedReadAlias, $config->getIndex(OperationType::READ));
        self::assertEquals($expectedWriteAlias, $config->getIndex(OperationType::WRITE));
    }

    public static function inflateConfigDataProvider(): array
    {
        return [
            'only_index_name_given'                            => [
                'input'              => ['index' => 'my_index'],
                'expectedReadAlias'  => 'my_index',
                'expectedWriteAlias' => 'my_index',
            ],
            'index_name_and_read_alias_given'                  => [
                'input'              => ['index' => 'my_index', 'index_read' => 'my_index_read'],
                'expectedReadAlias'  => 'my_index_read',
                'expectedWriteAlias' => 'my_index',
            ],
            'index_name_and_write_alias_given'                 => [
                'input'              => ['index' => 'my_index', 'index_write' => 'my_index_write'],
                'expectedReadAlias'  => 'my_index',
                'expectedWriteAlias' => 'my_index_write',
            ],
            'read_and_write_alias_given'                       => [
                'input'              => ['index_read' => 'my_index_read', 'index_write' => 'my_index_write'],
                'expectedReadAlias'  => 'my_index_read',
                'expectedWriteAlias' => 'my_index_write',
            ],
            'index_name_as_well_as_read_and_write_alias_given' => [
                'input'              => [
                    'index'       => 'this_will_be_ignored',
                    'index_read'  => 'my_index_read',
                    'index_write' => 'my_index_write',
                ],
                'expectedReadAlias'  => 'my_index_read',
                'expectedWriteAlias' => 'my_index_write',
            ],
        ];
    }

    #[DataProvider('noValidIndexConfiguredDataProvider')]
    public function testNoValidIndexConfigured(
        array $input,
        string $operationType,
        string $expectedExceptionMessage,
    ): void {
        $config = self::build($input);

        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $config->getIndex($operationType);
    }

    public static function noValidIndexConfiguredDataProvider(): array
    {
        $cases = [
            'nothing_given'          => [
                'input' => [],
            ],
            'empty_index_name_given' => [
                'input' => ['index' => ''],
            ],
            'empty_aliases_given'    => [
                'input' => ['index_read' => '', 'index_write' => ''],
            ],
            'null_index_name_given'  => [
                'input' => ['index' => null],
            ],
            'null_aliases_given'     => [
                'input' => ['index_read' => null, 'index_write' => null],
            ],
        ];

        $variations = [];
        foreach ($cases as $name => $data) {
            foreach ([OperationType::READ, OperationType::WRITE] as $operationType) {
                $data['operationType'] = $operationType;
                $data['expectedExceptionMessage'] = sprintf(
                    'No valid index name configured for operation "%s"',
                    $operationType
                );
                $variations[sprintf('operation_type_%s_%s', $operationType, $name)] = $data;
            }
        }

        return $variations;
    }

    public function testValidEntitySerializer(): void
    {
        $mySerializer = new class implements EntitySerializerInterface {
            public function toElastic($entity): array
            {
                return [];
            }
        };

        $config = self::build(['entity_serializer' => $mySerializer]);

        self::assertEquals($mySerializer, $config->getEntitySerializer());
    }

    public function testInvalidEntitySerializer(): void
    {
        $mySerializer = new stdClass();

        $this->expectException(TypeError::class);

        self::build(['entity_serializer' => $mySerializer]);
    }

    public function testValidEntityFactory(): void
    {
        $myFactory = new class implements EntityFactoryInterface {
            public function fromDocument(array $document, array $metaData): object
            {
                return new stdClass();
            }
        };

        $config = self::build(['entity_factory' => $myFactory]);

        self::assertEquals($myFactory, $config->getEntityFactory());
    }

    public function testInvalidEntityFactory(): void
    {
        $myFactory = new stdClass();

        $this->expectException(TypeError::class);

        self::build(['entity_factory' => $myFactory]);
    }

    public function testValidEntityClass(): void
    {
        $myEntity = new class implements PersistableEntityInterface {
            public function toElastic(): array
            {
                return [];
            }

            public static function fromElasticDocument(array $document, array $metaData): object
            {
                return new stdClass();
            }
        };

        $config = self::build(['entity_class' => $myEntity::class]);

        self::assertEquals($myEntity::class, $config->getEntityClass());
    }

    public function testInvalidEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid entity class given. Must be of type %s',
                PersistableEntityInterface::class
            )
        );

        self::build(['entity_class' => stdClass::class]);
    }

    public function testNonExistentEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage(
            'Given entity class does not exist.'
        );

        self::build(['entity_class' => '\Foo\Bar']);
    }

    #[DataProvider('forceRefreshOnWriteDataProvider')]
    public function testForceRefreshOnWrite(array $input, bool $expected): void
    {
        $config = self::build($input);

        self::assertEquals($expected, $config->getForceRefreshOnWrite());
    }

    public static function forceRefreshOnWriteDataProvider(): array
    {
        return [
            'param_not_given'                               => [
                'input'    => [
                    'index' => 'foobar',
                ],
                'expected' => false,
            ],
            'false_given'                                   => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => false,
                ],
                'expected' => false,
            ],
            'falsy_value_given'                             => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => 0,
                ],
                'expected' => false,
            ],
            'true_given'                                    => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => true,
                ],
                'expected' => true,
            ],
            'true-ish_integer_given'                        => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => 1,
                ],
                'expected' => true,
            ],
            'true-ish_string_given'                         => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => 'yes',
                ],
                'expected' => true,
            ],
            'not-so-clever-but-still-true-ish_string_given' => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => 'no',
                ],
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('trackTotalHitsDataProvider')]
    public function testTrackTotalHits(array $input, ?bool $expected): void
    {
        $config = self::build($input);

        self::assertEquals($expected, $config->getTrackTotalHits());
    }

    public static function trackTotalHitsDataProvider(): array
    {
        return [
            'param_not_given'                               => [
                'input'    => [
                    'index' => 'foobar',
                ],
                'expected' => null,
            ],
            'false_given'                                   => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => false,
                ],
                'expected' => false,
            ],
            'falsy_value_given'                             => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => 0,
                ],
                'expected' => false,
            ],
            'true_given'                                    => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => true,
                ],
                'expected' => true,
            ],
            'true-ish_integer_given'                        => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => 1,
                ],
                'expected' => true,
            ],
            'true-ish_string_given'                         => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => 'yes',
                ],
                'expected' => true,
            ],
            'not-so-clever-but-still-true-ish_string_given' => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => 'no',
                ],
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('scrollContextKeepaliveDataProvider')]
    public function testScrollContextKeepalive(array $input, string $expected): void
    {
        $config = self::build($input);

        self::assertEquals($expected, $config->getScrollContextKeepalive());
    }

    public static function scrollContextKeepaliveDataProvider(): array
    {
        return [
            'param_not_given'       => [
                'input'    => [
                    'index' => 'foobar',
                ],
                'expected' => '1m', // the default
            ],
            'valid_time_unit_given' => [
                'input'    => [
                    'index'                    => 'foobar',
                    'scroll_context_keepalive' => '10m',
                ],
                'expected' => '10m',
            ],
        ];
    }

    public function testInvalidScrollContextKeepalive(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid value for scroll_context_keepalive given. Must be a valid time unit.'
        );

        self::build(['index' => 'foobar', 'scroll_context_keepalive' => 'xxx']);
    }

    public function testGetDefaultScrollContextKeepalive(): void
    {
        $config = self::build();

        self::assertEquals('1m', $config->getScrollContextKeepalive());
        self::assertFalse($config->getForceRefreshOnWrite());
    }

    private static function build(array $config = []): RepositoryConfiguration
    {
        return new RepositoryConfiguration($config);
    }
}
