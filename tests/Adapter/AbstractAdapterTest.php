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
     * @param string|null $index
     * @param string|null $type
     *
     * @return \Kununu\Elasticsearch\Adapter\AbstractAdapter
     */
    protected function getInstance(?string $index, ?string $type)
    {
        return new class($index, $type) extends AbstractAdapter
        {
            public function __construct(?string $index, ?string $type)
            {
                $this->indexName = $index;
                $this->typeName = $type;

                $this->validateIndexAndType();
            }
        };
    }

    public function invalidIndexOrTypeData(): array
    {
        return [
            ['something', null],
            [null, 'foo'],
            ['something', ''],
            ['', 'foo'],
            ['', null],
            [null, ''],
            [null, null],
            ['', ''],
        ];
    }

    /**
     * @dataProvider invalidIndexOrTypeData
     *
     * @param string|null $index
     * @param string|null $type
     */
    public function testInvalidIndexOrType(?string $index, ?string $type): void
    {
        $this->expectException(AdapterConfigurationException::class);

        $this->getInstance($index, $type);
    }
}
