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
use TypeError;

class RepositoryConfigurationTest extends TestCase
{
    public function testGetDefaultScrollContextKeepalive(): void
    {
        $config = new RepositoryConfiguration([]);

        $this->assertSame('1m', $config->getScrollContextKeepalive());
        $this->assertFalse($config->getForceRefreshOnWrite());
    }

    public function inflatableConfigData(): array
    {
        return [
            'only index name given' => [
                'input' => ['index' => 'my_index'],
                'expected_read_alias' => 'my_index',
                'expected_write_alias' => 'my_index',
            ],
            'index name and read alias given' => [
                'input' => ['index' => 'my_index', 'index_read' => 'my_index_read'],
                'expected_read_alias' => 'my_index_read',
                'expected_write_alias' => 'my_index',
            ],
            'index name and write alias given' => [
                'input' => ['index' => 'my_index', 'index_write' => 'my_index_write'],
                'expected_read_alias' => 'my_index',
                'expected_write_alias' => 'my_index_write',
            ],
            'read and write alias given' => [
                'input' => ['index_read' => 'my_index_read', 'index_write' => 'my_index_write'],
                'expected_read_alias' => 'my_index_read',
                'expected_write_alias' => 'my_index_write',
            ],
            'index name as well as read and write alias given' => [
                'input' => [
                    'index' => 'this_will_be_ignored',
                    'index_read' => 'my_index_read',
                    'index_write' => 'my_index_write',
                ],
                'expected_read_alias' => 'my_index_read',
                'expected_write_alias' => 'my_index_write',
            ],
        ];
    }

    /**
     * @dataProvider inflatableConfigData
     */
    public function testInflateConfig(array $input, string $expectedReadAlias, string $expectedWriteAlias): void
    {
        $config = new RepositoryConfiguration($input);

        $this->assertEquals($expectedReadAlias, $config->getIndex(OperationType::READ));
        $this->assertEquals($expectedWriteAlias, $config->getIndex(OperationType::WRITE));
    }

    public function invalidIndexConfigData(): array
    {
        $cases = [
            'nothing given' => [
                'input' => [],
            ],
            'empty index name given' => [
                'input' => ['index' => ''],
            ],
            'empty aliases given' => [
                'input' => ['index_read' => '', 'index_write' => ''],
            ],
            'null index name given' => [
                'input' => ['index' => null],
            ],
            'null aliases given' => [
                'input' => ['index_read' => null, 'index_write' => null],
            ],
        ];

        $variations = [];
        foreach ($cases as $name => $data) {
            foreach ([OperationType::READ, OperationType::WRITE] as $operationType) {
                $data['operation_type'] = $operationType;
                $data['expected_exception_msg'] = 'No valid index name configured for operation "' . $operationType . '"';
                $variations['operation type ' . $operationType . ' ' . $name] = $data;
            }
        }

        return $variations;
    }

    /**
     * @dataProvider invalidIndexConfigData
     */
    public function testNoValidIndexConfigured(array $input, string $operationType, string $expectedExceptionMsg): void
    {
        $config = new RepositoryConfiguration($input);

        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMsg);

        $config->getIndex($operationType);
    }

    public function invalidTypeConfigData(): array
    {
        return [
            'nothing given' => [
                'input' => [],
            ],
            'empty type name given' => [
                'input' => ['type' => ''],
            ],
            'null type name given' => [
                'input' => ['type' => null],
            ],
        ];
    }

    /**
     * @dataProvider invalidIndexConfigData
     */
    public function testNoValidTypeConfigured(array $input): void
    {
        $config = new RepositoryConfiguration($input);

        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No valid type configured');

        $config->getType();
    }

    public function testValidEntitySerializer(): void
    {
        $mySerializer = new class implements EntitySerializerInterface
        {
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
        $mySerializer = new \stdClass();

        $this->expectException(TypeError::class);

        $config = new RepositoryConfiguration(['entity_serializer' => $mySerializer]); // NOSONAR
    }

    public function testValidEntityFactory(): void
    {
        $myFactory = new class implements EntityFactoryInterface
        {
            public function fromDocument(array $document, array $metaData): object
            {
                return new \stdClass();
            }
        };

        $config = new RepositoryConfiguration(['entity_factory' => $myFactory]);

        $this->assertEquals($myFactory, $config->getEntityFactory());
    }

    public function testInvalidEntityFactory(): void
    {
        $myFactory = new \stdClass();

        $this->expectException(TypeError::class);

        $config = new RepositoryConfiguration(['entity_factory' => $myFactory]); // NOSONAR
    }

    public function testValidEntityClass(): void
    {
        $myEntity = new class implements PersistableEntityInterface
        {
            public function toElastic(): array
            {
                return [];
            }

            public static function fromElasticDocument(array $document, array $metaData): object
            {
                return new \stdClass();
            }
        };

        $config = new RepositoryConfiguration(['entity_class' => get_class($myEntity)]);

        $this->assertEquals(get_class($myEntity), $config->getEntityClass());
    }

    public function testInvalidEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid entity class given. Must be of type \Kununu\Elasticsearch\Repository\PersistableEntityInterface'
        );

        $config = new RepositoryConfiguration(['entity_class' => \stdClass::class]); // NOSONAR
    }

    public function testNonExistentEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage(
            'Given entity class does not exist.'
        );

        $config = new RepositoryConfiguration(['entity_class' => '\Foo\Bar']); // NOSONAR
    }

    public function forceRefreshOnWriteVariations(): array
    {
        return [
            'param not given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                ],
                'expected' => false,
            ],
            'false given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'force_refresh_on_write' => false,
                ],
                'expected' => false,
            ],
            'falsy value given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'force_refresh_on_write' => 0,
                ],
                'expected' => false,
            ],
            'true given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'force_refresh_on_write' => true,
                ],
                'expected' => true,
            ],
            'true-ish integer given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'force_refresh_on_write' => 1,
                ],
                'expected' => true,
            ],
            'true-ish string given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'force_refresh_on_write' => 'yes',
                ],
                'expected' => true,
            ],
            'not-so-clever-but-still-true-ish string given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'force_refresh_on_write' => 'no',
                ],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider forceRefreshOnWriteVariations
     */
    public function testForceRefreshOnWrite(array $input, bool $expected): void
    {
        $config = new RepositoryConfiguration($input);

        $this->assertSame($expected, $config->getForceRefreshOnWrite());
    }

    public function trackTotalHitsVariations(): array
    {
        return [
            'param not given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                ],
                'expected' => null,
            ],
            'false given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'track_total_hits' => false,
                ],
                'expected' => false,
            ],
            'falsy value given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'track_total_hits' => 0,
                ],
                'expected' => false,
            ],
            'true given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'track_total_hits' => true,
                ],
                'expected' => true,
            ],
            'true-ish integer given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'track_total_hits' => 1,
                ],
                'expected' => true,
            ],
            'true-ish string given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'track_total_hits' => 'yes',
                ],
                'expected' => true,
            ],
            'not-so-clever-but-still-true-ish string given' => [
                'input' => [
                    'index' => 'foobar',
                    'type' => '_doc',
                    'track_total_hits' => 'no',
                ],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider trackTotalHitsVariations
     */
    public function testTrackTotalHits(array $input, ?bool $expected): void
    {
        $config = new RepositoryConfiguration($input);

        $this->assertSame($expected, $config->getTrackTotalHits());
    }
}
