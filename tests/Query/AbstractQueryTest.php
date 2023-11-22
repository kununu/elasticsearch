<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\AbstractQuery;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Query\SortOrder;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class AbstractQueryTest extends MockeryTestCase
{
    public function testBuildBaseBodyEmpty(): void
    {
        $query = $this->getQuery();

        $this->assertEquals([], $query->toArray());
    }

    /** @dataProvider selectDataProvider */
    public function testSelect(mixed $input, mixed $expectedOutput): void
    {
        $query = $this->getQuery();

        $this->assertNull($query->getSelect());
        $this->assertEquals([], $query->toArray());

        $query->select($input);

        $this->assertEquals($input, $query->getSelect());
        $serialized = $query->toArray();
        $this->assertArrayHasKey('_source', $serialized);
        $this->assertEquals($expectedOutput, $serialized['_source']);
    }

    public static function selectDataProvider(): array
    {
        return [
            'no field'         => [
                'input'           => [],
                'expected_output' => false,
            ],
            'one field'        => [
                'input'           => [
                    'foo',
                ],
                'expected_output' => [
                    'foo',
                ],
            ],
            'two fields'       => [
                'input'           => [
                    'foo',
                    'bar',
                ],
                'expected_output' => [
                    'foo',
                    'bar',
                ],
            ],
            'same field twice' => [
                'input'           => [
                    'foo',
                    'foo',
                ],
                'expected_output' => [
                    'foo',
                ],
            ],
        ];
    }

    public function testSkip(): void
    {
        $query = $this->getQuery();

        $this->assertNull($query->getOffset());
        $this->assertEquals([], $query->toArray());

        $query->skip(10);

        $this->assertEquals(10, $query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals([], $query->getSort());
        $this->assertEquals(['from' => 10], $query->toArray());

        // override offset
        $query->skip(20);

        $this->assertEquals(20, $query->getOffset());
        $this->assertEquals(['from' => 20], $query->toArray());
    }

    public function testLimit(): void
    {
        $query = $this->getQuery();

        $this->assertNull($query->getLimit());
        $this->assertEquals([], $query->toArray());

        $query->limit(10);

        $this->assertEquals(10, $query->getLimit());
        $this->assertNull($query->getOffset());
        $this->assertEquals([], $query->getSort());
        $this->assertEquals(['size' => 10], $query->toArray());

        // override limit
        $query->limit(20);

        $this->assertEquals(20, $query->getLimit());
        $this->assertEquals(['size' => 20], $query->toArray());
    }

    /** @dataProvider sortDataProvider */
    public function testSort(array $input, array $expectedOutput): void
    {
        $query = $this->getQuery();

        $this->assertEquals([], $query->getSort());
        $this->assertEquals([], $query->toArray());

        foreach ($input as $command) {
            $query->sort($command['key'], $command['order'], $command['options'] ?? []);
        }

        $this->assertNull($query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals($expectedOutput, $query->getSort());
        $this->assertEquals(['sort' => $expectedOutput], $query->toArray());
    }

    public static function sortDataProvider(): array
    {
        return [
            'one field'               => [
                'input'           => [
                    [
                        'key'   => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                ],
                'expected_output' => [
                    'foo' => ['order' => SortOrder::ASC],
                ],
            ],
            'two fields'              => [
                'input'           => [
                    [
                        'key'   => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                    [
                        'key'   => 'bar',
                        'order' => SortOrder::DESC,
                    ],
                ],
                'expected_output' => [
                    'foo' => ['order' => SortOrder::ASC],
                    'bar' => ['order' => SortOrder::DESC],
                ],
            ],
            'override one field'      => [
                'input'           => [
                    [
                        'key'   => 'foo',
                        'order' => SortOrder::ASC,
                    ],
                    [
                        'key'   => 'foo',
                        'order' => SortOrder::DESC,
                    ],
                ],
                'expected_output' => [
                    'foo' => ['order' => SortOrder::DESC],
                ],
            ],
            'one field with options'  => [
                'input'           => [
                    [
                        'key'     => 'foo',
                        'order'   => SortOrder::ASC,
                        'options' => ['missing' => '_last'],
                    ],
                ],
                'expected_output' => [
                    'foo' => ['order' => SortOrder::ASC, 'missing' => '_last'],
                ],
            ],
            'two fields with options' => [
                'input'           => [
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
                'expected_output' => [
                    'foo' => ['order' => SortOrder::ASC, 'missing' => '_last'],
                    'bar' => ['order' => SortOrder::DESC, 'mode' => 'avg'],
                ],
            ],
        ];
    }

    /** @dataProvider sortDataProvider */
    public function testMultipleSort(array $input, array $expectedOutput): void
    {
        $query = $this->getQuery();

        $this->assertEquals([], $query->getSort());
        $this->assertEquals([], $query->toArray());

        $combinedInput = array_reduce(
            $input,
            function(array $carry, array $command): array {
                $carry[$command['key']] = $command;

                return $carry;
            },
            []
        );

        $query->sort($combinedInput);

        $this->assertNull($query->getOffset());
        $this->assertNull($query->getLimit());
        $this->assertEquals($expectedOutput, $query->getSort());
        $this->assertEquals(['sort' => $expectedOutput], $query->toArray());
    }

    public function testSortInvalidOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getQuery()->sort('foo', 'bar');
    }

    private function getQuery(): QueryInterface
    {
        return new class() extends AbstractQuery {
            public function toArray(): array
            {
                return $this->buildBaseBody();
            }
        };
    }
}
