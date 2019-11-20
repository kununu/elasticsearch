<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Adapter;

use Kununu\Elasticsearch\Adapter\AbstractAdapter;
use Kununu\Elasticsearch\Exception\AdapterConfigurationException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class AbstractAdapterTest extends MockeryTestCase
{
    protected const INDEX = 'some_index';
    protected const TYPE = '_doc';

    /**
     * @param array|null  $index
     * @param string|null $type
     *
     * @return \Kununu\Elasticsearch\Adapter\AbstractAdapter
     */
    protected function getInstance(?array $index, ?string $type)
    {
        return new class($index, $type) extends AbstractAdapter
        {
            public function __construct(?array $index, ?string $type)
            {
                $this->index = $index;
                $this->typeName = $type;

                $this->validateIndexAndType();
            }
        };
    }

    public function invalidIndexOrTypeData(): array
    {
        return [
            [['index_read' => '', 'index_write' => 'something'], 'foo'],
            [['index_read' => 'something', 'index_write' => ''], 'foo'],
            [['index_read' => '', 'index_write' => ''], 'foo'],
            [['index_read' => 'something'], 'foo'],
            [['index_write' => 'something'], 'foo'],
            [['index_read' => ''], 'foo'],
            [['index_write' => ''], 'foo'],
            [[], 'foo'],
            [null, 'foo'],
            [['index_read' => 'something', 'index_write' => 'something'], ''],
            [['index_read' => 'something', 'index_write' => 'something'], null],
            [[], ''],
            [[], null],
            [null, ''],
            [null, null],
            [['index_read' => '', 'index_write' => 'something'], ''],
            [['index_read' => 'something', 'index_write' => ''], ''],
            [['index_read' => '', 'index_write' => ''], ''],
            [['index_read' => 'something'], ''],
            [['index_write' => 'something'], ''],
            [['index_read' => ''], ''],
            [['index_write' => ''], ''],
            [['index_read' => '', 'index_write' => 'something'], null],
            [['index_read' => 'something', 'index_write' => ''], null],
            [['index_read' => '', 'index_write' => ''], null],
            [['index_read' => 'something'], null],
            [['index_write' => 'something'], null],
            [['index_read' => ''], null],
            [['index_write' => ''], null],
        ];
    }

    /**
     * @dataProvider invalidIndexOrTypeData
     *
     * @param array|null  $index
     * @param string|null $type
     */
    public function testInvalidIndexOrType(?array $index, ?string $type): void
    {
        $this->expectException(AdapterConfigurationException::class);

        $this->getInstance($index, $type);
    }
}
