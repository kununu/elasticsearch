<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Result;

use Kununu\Elasticsearch\Result\AggregationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AggregationResultTest extends TestCase
{
    public static function createDataProvider(): array
    {
        return [
            'empty_name_empty_fields'         => [
                'name'   => '',
                'fields' => [],
            ],
            'non_empty_name_empty_fields'     => [
                'name'   => 'my_agg',
                'fields' => [],
            ],
            'empty_name_non_empty_fields'     => [
                'name'   => '',
                'fields' => ['some' => 'thing', 'foo' => 'bar'],
            ],
            'non_empty_name_non_empty_fields' => [
                'name'   => 'my_agg',
                'fields' => ['some' => 'thing', 'foo' => 'bar'],
            ],
        ];
    }

    #[DataProvider('createDataProvider')]
    public function testCreate(string $name, array $fields): void
    {
        $result = AggregationResult::create($name, $fields);

        self::assertEquals([$name => $fields], $result->toArray());
    }

    #[DataProvider('createDataProvider')]
    public function testGetName(string $name, array $fields): void
    {
        $result = AggregationResult::create($name, $fields);

        self::assertEquals($name, $result->getName());
    }

    #[DataProvider('createDataProvider')]
    public function testGetFields(string $name, array $fields): void
    {
        $result = AggregationResult::create($name, $fields);

        self::assertEquals($fields, $result->getFields());
    }

    public function testGetField(): void
    {
        $result = AggregationResult::create('my_agg', ['some' => 'thing', 'foo' => 'bar']);

        self::assertEquals('thing', $result->get('some'));
        self::assertEquals('bar', $result->get('foo'));
        self::assertNull($result->get('this_field_does_not_exist'));
    }

    public function testGetBuckets(): void
    {
        $result = AggregationResult::create(
            'my_agg',
            ['buckets' => ['a' => ['first_bucket'], 'b' => ['second_bucket']]]
        );

        self::assertEquals(['a' => ['first_bucket'], 'b' => ['second_bucket']], $result->getBuckets());

        $result = AggregationResult::create(
            'my_agg',
            []
        );

        self::assertNull($result->getBuckets());
    }

    public function getValue(): void
    {
        $result = AggregationResult::create(
            'my_agg',
            ['value' => 0.1]
        );

        self::assertEquals(0.1, $result->getValue());

        $result = AggregationResult::create(
            'my_agg',
            []
        );

        self::assertNull($result->getValue());
    }
}
