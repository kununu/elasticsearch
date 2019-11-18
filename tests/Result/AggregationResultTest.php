<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Adapter;

use Kununu\Elasticsearch\Result\AggregationResult;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class AggregationResultTest extends MockeryTestCase
{
    public function createData(): array
    {
        return [
            'empty name, empty fields' => [
                'name' => '',
                'fields' => [],
            ],
            'non-empty name, empty fields' => [
                'name' => 'my_agg',
                'fields' => [],
            ],
            'empty name, non-empty fields' => [
                'name' => '',
                'fields' => ['some' => 'thing', 'foo' => 'bar'],
            ],
            'non-empty name, non-empty fields' => [
                'name' => 'my_agg',
                'fields' => ['some' => 'thing', 'foo' => 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider createData
     *
     * @param string $name
     * @param array  $fields
     */
    public function testCreate(string $name, array $fields): void
    {
        $result = AggregationResult::create($name, $fields);
        $this->assertEquals([$name => $fields], $result->toArray());
    }

    /**
     * @dataProvider createData
     *
     * @param string $name
     * @param array  $fields
     */
    public function testGetName(string $name, array $fields): void
    {
        $result = AggregationResult::create($name, $fields);
        $this->assertEquals($name, $result->getName());
    }

    /**
     * @dataProvider createData
     *
     * @param string $name
     * @param array  $fields
     */
    public function testGetFields(string $name, array $fields): void
    {
        $result = AggregationResult::create($name, $fields);
        $this->assertEquals($fields, $result->getFields());
    }

    public function testGetField(): void
    {
        $result = AggregationResult::create('my_agg', ['some' => 'thing', 'foo' => 'bar']);
        $this->assertEquals('thing', $result->get('some'));
        $this->assertEquals('bar', $result->get('foo'));
        $this->assertNull($result->get('this_field_does_not_exist'));
    }

    public function testGetBuckets(): void
    {
        $result = AggregationResult::create(
            'my_agg',
            ['buckets' => ['a' => ['first_bucket'], 'b' => ['second_bucket']]]
        );
        $this->assertEquals(['a' => ['first_bucket'], 'b' => ['second_bucket']], $result->getBuckets());

        $result = AggregationResult::create(
            'my_agg',
            []
        );
        $this->assertNull($result->getBuckets());
    }

    public function getValue(): void
    {
        $result = AggregationResult::create(
            'my_agg',
            ['value' => 0.1]
        );
        $this->assertEquals(0.1, $result->getValue());

        $result = AggregationResult::create(
            'my_agg',
            []
        );
        $this->assertNull($result->getValue());
    }
}
