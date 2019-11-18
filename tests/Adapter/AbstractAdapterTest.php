<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Adapter\AbstractAdapter;
use App\Services\Elasticsearch\Exception\AdapterConfigurationException;
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
     * @return \App\Services\Elasticsearch\Adapter\AbstractAdapter
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
