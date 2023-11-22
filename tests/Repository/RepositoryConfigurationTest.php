<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Repository\EntityFactoryInterface;
use Kununu\Elasticsearch\Repository\EntitySerializerInterface;
use Kununu\Elasticsearch\Repository\OperationType;
use Kununu\Elasticsearch\Repository\PersistableEntityInterface;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

final class RepositoryConfigurationTest extends TestCase
{
    /** @dataProvider inflateConfigDataProvider */
    public function testInflateConfig(array $input, string $expectedReadAlias, string $expectedWriteAlias): void
    {
        $config = new RepositoryConfiguration($input);

        $this->assertEquals($expectedReadAlias, $config->getIndex(OperationType::READ));
        $this->assertEquals($expectedWriteAlias, $config->getIndex(OperationType::WRITE));
    }

    public static function inflateConfigDataProvider(): array
    {
        return [
            'only index name given'                            => [
                'input'                => ['index' => 'my_index'],
                'expected_read_alias'  => 'my_index',
                'expected_write_alias' => 'my_index',
            ],
            'index name and read alias given'                  => [
                'input'                => ['index' => 'my_index', 'index_read' => 'my_index_read'],
                'expected_read_alias'  => 'my_index_read',
                'expected_write_alias' => 'my_index',
            ],
            'index name and write alias given'                 => [
                'input'                => ['index' => 'my_index', 'index_write' => 'my_index_write'],
                'expected_read_alias'  => 'my_index',
                'expected_write_alias' => 'my_index_write',
            ],
            'read and write alias given'                       => [
                'input'                => ['index_read' => 'my_index_read', 'index_write' => 'my_index_write'],
                'expected_read_alias'  => 'my_index_read',
                'expected_write_alias' => 'my_index_write',
            ],
            'index name as well as read and write alias given' => [
                'input'                => [
                    'index'       => 'this_will_be_ignored',
                    'index_read'  => 'my_index_read',
                    'index_write' => 'my_index_write',
                ],
                'expected_read_alias'  => 'my_index_read',
                'expected_write_alias' => 'my_index_write',
            ],
        ];
    }

    /** @dataProvider noValidIndexConfiguredDataProvider */
    public function testNoValidIndexConfigured(array $input, string $operationType, string $expectedExceptionMsg): void
    {
        $config = new RepositoryConfiguration($input);

        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMsg);

        $config->getIndex($operationType);
    }

    public static function noValidIndexConfiguredDataProvider(): array
    {
        $cases = [
            'nothing given'          => [
                'input' => [],
            ],
            'empty index name given' => [
                'input' => ['index' => ''],
            ],
            'empty aliases given'    => [
                'input' => ['index_read' => '', 'index_write' => ''],
            ],
            'null index name given'  => [
                'input' => ['index' => null],
            ],
            'null aliases given'     => [
                'input' => ['index_read' => null, 'index_write' => null],
            ],
        ];

        $variations = [];
        foreach ($cases as $name => $data) {
            foreach ([OperationType::READ, OperationType::WRITE] as $operationType) {
                $data['operation_type'] = $operationType;
                $data['expected_exception_msg'] =
                    'No valid index name configured for operation "' . $operationType . '"';
                $variations['operation type ' . $operationType . ' ' . $name] = $data;
            }
        }

        return $variations;
    }

    public function testValidEntitySerializer(): void
    {
        $mySerializer = new class() implements EntitySerializerInterface {
            public function toElastic($entity): array
            {
                return [];
            }
        };

        $config = new RepositoryConfiguration(['entity_serializer' => $mySerializer]);

        $this->assertEquals($mySerializer, $config->getEntitySerializer());
    }

    public function testInvalidEntitySerializer(): void
    {
        $mySerializer = new stdClass();

        $this->expectException(TypeError::class);

        new RepositoryConfiguration(['entity_serializer' => $mySerializer]);
    }

    public function testValidEntityFactory(): void
    {
        $myFactory = new class() implements EntityFactoryInterface {
            public function fromDocument(array $document, array $metaData): object
            {
                return new stdClass();
            }
        };

        $config = new RepositoryConfiguration(['entity_factory' => $myFactory]);

        $this->assertEquals($myFactory, $config->getEntityFactory());
    }

    public function testInvalidEntityFactory(): void
    {
        $myFactory = new stdClass();

        $this->expectException(TypeError::class);

        new RepositoryConfiguration(['entity_factory' => $myFactory]);
    }

    public function testValidEntityClass(): void
    {
        $myEntity = new class() implements PersistableEntityInterface {
            public function toElastic(): array
            {
                return [];
            }

            public static function fromElasticDocument(array $document, array $metaData): object
            {
                return new stdClass();
            }
        };

        $config = new RepositoryConfiguration(['entity_class' => $myEntity::class]);

        $this->assertEquals($myEntity::class, $config->getEntityClass());
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

        new RepositoryConfiguration(['entity_class' => stdClass::class]);
    }

    public function testNonExistentEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage(
            'Given entity class does not exist.'
        );

        new RepositoryConfiguration(['entity_class' => '\Foo\Bar']);
    }

    /** @dataProvider forceRefreshOnWriteDataProvider */
    public function testForceRefreshOnWrite(array $input, bool $expected): void
    {
        $config = new RepositoryConfiguration($input);

        $this->assertSame($expected, $config->getForceRefreshOnWrite());
    }

    public static function forceRefreshOnWriteDataProvider(): array
    {
        return [
            'param not given'                               => [
                'input'    => [
                    'index' => 'foobar',
                ],
                'expected' => false,
            ],
            'false given'                                   => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => false,
                ],
                'expected' => false,
            ],
            'falsy value given'                             => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => 0,
                ],
                'expected' => false,
            ],
            'true given'                                    => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => true,
                ],
                'expected' => true,
            ],
            'true-ish integer given'                        => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => 1,
                ],
                'expected' => true,
            ],
            'true-ish string given'                         => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => 'yes',
                ],
                'expected' => true,
            ],
            'not-so-clever-but-still-true-ish string given' => [
                'input'    => [
                    'index'                  => 'foobar',
                    'force_refresh_on_write' => 'no',
                ],
                'expected' => true,
            ],
        ];
    }

    /** @dataProvider trackTotalHitsDataProvider */
    public function testTrackTotalHits(array $input, ?bool $expected): void
    {
        $config = new RepositoryConfiguration($input);

        $this->assertSame($expected, $config->getTrackTotalHits());
    }

    public static function trackTotalHitsDataProvider(): array
    {
        return [
            'param not given'                               => [
                'input'    => [
                    'index' => 'foobar',
                ],
                'expected' => null,
            ],
            'false given'                                   => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => false,
                ],
                'expected' => false,
            ],
            'falsy value given'                             => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => 0,
                ],
                'expected' => false,
            ],
            'true given'                                    => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => true,
                ],
                'expected' => true,
            ],
            'true-ish integer given'                        => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => 1,
                ],
                'expected' => true,
            ],
            'true-ish string given'                         => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => 'yes',
                ],
                'expected' => true,
            ],
            'not-so-clever-but-still-true-ish string given' => [
                'input'    => [
                    'index'            => 'foobar',
                    'track_total_hits' => 'no',
                ],
                'expected' => true,
            ],
        ];
    }

    /** @dataProvider scrollContextKeepaliveDataProvider */
    public function testScrollContextKeepalive(array $input, string $expected): void
    {
        $config = new RepositoryConfiguration($input);

        $this->assertSame($expected, $config->getScrollContextKeepalive());
    }

    public static function scrollContextKeepaliveDataProvider(): array
    {
        return [
            'param not given'       => [
                'input'    => [
                    'index' => 'foobar',
                ],
                'expected' => '1m', // the default
            ],
            'valid time unit given' => [
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

        new RepositoryConfiguration(['index' => 'foobar', 'scroll_context_keepalive' => 'xxx']);
    }

    public function testGetDefaultScrollContextKeepalive(): void
    {
        $config = new RepositoryConfiguration([]);

        $this->assertSame('1m', $config->getScrollContextKeepalive());
        $this->assertFalse($config->getForceRefreshOnWrite());
    }
}
