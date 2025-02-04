<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\AbstractBaseQuery;
use Kununu\Elasticsearch\Query\SortOrder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AbstractBaseQueryTest extends TestCase
{
    public function testBuildBaseBodyEmpty(): void
    {
        $query = $this->getQuery();

        self::assertEquals([], $query->toArray());
    }

    #[DataProvider('selectDataProvider')]
    public function testSelect(mixed $input, mixed $expectedOutput): void
    {
        $query = $this->getQuery();

        self::assertNull($query->getSelect());
        self::assertEquals([], $query->toArray());

        $query->select($input);

        self::assertEquals($input, $query->getSelect());

        $serialized = $query->toArray();

        self::assertArrayHasKey('_source', $serialized);
        self::assertEquals($expectedOutput, $serialized['_source']);
    }

    public static function selectDataProvider(): array
    {
        return [
            'no_field'         => [
                'input'          => [],
                'expectedOutput' => false,
            ],
            'one_field'        => [
                'input'          => [
                    'foo',
                ],
                'expectedOutput' => [
                    'foo',
                ],
            ],
            'two_fields'       => [
                'input'          => [
                    'foo',
                    'bar',
                ],
                'expectedOutput' => [
                    'foo',
                    'bar',
                ],
            ],
            'same_field_twice' => [
                'input'          => [
                    'foo',
                    'foo',
                ],
                'expectedOutput' => [
                    'foo',
                ],
            ],
        ];
    }

    public function testSkip(): void
    {
        $query = $this->getQuery();

        self::assertNull($query->getOffset());
        self::assertEquals([], $query->toArray());

        $query->skip(10);

        self::assertEquals(10, $query->getOffset());
        self::assertNull($query->getLimit());
        self::assertEquals([], $query->getSort());
        self::assertEquals(['from' => 10], $query->toArray());

        // override offset
        $query->skip(20);

        self::assertEquals(20, $query->getOffset());
        self::assertEquals(['from' => 20], $query->toArray());
    }

    public function testLimit(): void
    {
        $query = $this->getQuery();

        self::assertNull($query->getLimit());
        self::assertEquals([], $query->toArray());

        $query->limit(10);

        self::assertEquals(10, $query->getLimit());
        self::assertNull($query->getOffset());
        self::assertEquals([], $query->getSort());
        self::assertEquals(['size' => 10], $query->toArray());

        // override limit
        $query->limit(20);

        self::assertEquals(20, $query->getLimit());
        self::assertEquals(['size' => 20], $query->toArray());
    }

    #[DataProvider('sortDataProvider')]
    public function testSort(array $input, array $expectedOutput): void
    {
        $query = $this->getQuery();

        self::assertEquals([], $query->getSort());
        self::assertEquals([], $query->toArray());

        foreach ($input as $command) {
            $query->sort($command['key'], $command['order'], $command['options'] ?? []);
        }

        self::assertNull($query->getOffset());
        self::assertNull($query->getLimit());
        self::assertEquals($expectedOutput, $query->getSort());
        self::assertEquals(['sort' => $expectedOutput], $query->toArray());
    }

    public static function sortDataProvider(): array
    {
        return [
            'one_field'               => [
                'input'          => [
                    [
                        'key'   => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                ],
                'expectedOutput' => [
                    'foo' => ['order' => SortOrder::ASC],
                ],
            ],
            'two_fields'              => [
                'input'          => [
                    [
                        'key'   => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                    [
                        'key'   => 'bar',
                        'order' => SortOrder::DESC,
                    ],
                ],
                'expectedOutput' => [
                    'foo' => ['order' => SortOrder::ASC],
                    'bar' => ['order' => SortOrder::DESC],
                ],
            ],
            'override_one_field'      => [
                'input'          => [
                    [
                        'key'   => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                    [
                        'key'   => 'foo',
                        'order' => SortOrder::DESC,
                    ],
                ],
                'expectedOutput' => [
                    'foo' => ['order' => SortOrder::DESC],
                ],
            ],
            'one_field_with_options'  => [
                'input'          => [
                    [
                        'key'     => 'foo',
                        'order'   => SortOrder::ASC,
                        'options' => ['missing' => '_last'],
                    ],
                ],
                'expectedOutput' => [
                    'foo' => ['order' => SortOrder::ASC, 'missing' => '_last'],
                ],
            ],
            'two_fields_with_options' => [
                'input'          => [
                    [
                        'key'     => 'foo',
                        'order'   => SortOrder::ASC,
                        'options' => ['missing' => '_last'],
                    ],
                    [
                        'key'     => 'bar',
                        'order'   => SortOrder::DESC,
                        'options' => ['mode' => 'avg'],
                    ],
                ],
                'expectedOutput' => [
                    'foo' => ['order' => SortOrder::ASC, 'missing' => '_last'],
                    'bar' => ['order' => SortOrder::DESC, 'mode' => 'avg'],
                ],
            ],
        ];
    }

    #[DataProvider('sortDataProvider')]
    public function testMultipleSort(array $input, array $expectedOutput): void
    {
        $query = $this->getQuery();

        self::assertEquals([], $query->getSort());
        self::assertEquals([], $query->toArray());

        $combinedInput = array_reduce(
            $input,
            function(array $carry, array $command): array {
                $carry[$command['key']] = $command;

                return $carry;
            },
            []
        );

        $query->sort($combinedInput);

        self::assertNull($query->getOffset());
        self::assertNull($query->getLimit());
        self::assertEquals($expectedOutput, $query->getSort());
        self::assertEquals(['sort' => $expectedOutput], $query->toArray());
    }

    public function testSortInvalidOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getQuery()->sort('foo', 'bar');
    }

    private function getQuery(): AbstractBaseQuery
    {
        return new class extends AbstractBaseQuery {
            public function toArray(): array
            {
                return $this->buildBaseBody();
            }
        };
    }
}
