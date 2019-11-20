<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Repository\OperationType;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use PHPUnit\Framework\TestCase;

class RepositoryConfigurationTest extends TestCase
{
    public function testGetDefaultScrollContextKeepalive(): void
    {
        $config = new RepositoryConfiguration([]);

        $this->assertEquals('1m', $config->getScrollContextKeepalive());
    }

    /**
     * @return array
     */
    public function configData(): array
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
     * @dataProvider configData
     *
     * @param array  $input
     * @param string $expectedReadAlias
     * @param string $expectedWriteAlias
     */
    public function testInflateConfig(array $input, string $expectedReadAlias, string $expectedWriteAlias): void
    {
        $config = new RepositoryConfiguration($input);

        $this->assertEquals($expectedReadAlias, $config->getIndex(OperationType::READ));
        $this->assertEquals($expectedWriteAlias, $config->getIndex(OperationType::WRITE));
    }

    /**
     * @return array
     */
    public function invalidIndexConfigData(): array
    {
        $cases = [
            'noting given' => [
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
     *
     * @param array  $input
     * @param string $operationType
     * @param string $expectedExceptionMsg
     */
    public function testNoValidIndexConfigured(array $input, string $operationType, string $expectedExceptionMsg): void
    {
        $config = new RepositoryConfiguration($input);

        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMsg);

        $config->getIndex($operationType);
    }

    /**
     * @return array
     */
    public function invalidTypeConfigData(): array
    {
        return [
            'noting given' => [
                'input' => [],
            ],
            'empty type name given' => [
                'input' => ['type' => ''],
            ],
            'null type name given' => [
                'input' => ['index' => null],
            ],
        ];
    }

    /**
     * @dataProvider invalidIndexConfigData
     *
     * @param array $input
     */
    public function testNoValidTypeConfigured(array $input): void
    {
        $config = new RepositoryConfiguration($input);

        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No valid type configured');

        $config->getType();
    }
}
