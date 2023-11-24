<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Aggregation;
use Kununu\Elasticsearch\Query\Aggregation\Bucket;
use Kununu\Elasticsearch\Query\Aggregation\Metric;
use PHPUnit\Framework\TestCase;
use stdClass;

final class AggregationTest extends TestCase
{
    /** @dataProvider createDataProvider */
    public function testCreateWithoutOptions(string $field, string $type, string $name): void
    {
        $aggregation = Aggregation::create($field, $type, $name);

        $this->assertEquals($name, $aggregation->getName());
        $this->assertEquals(
            [
                $name => [
                    $type => [
                        'field' => $field,
                    ],
                ],
            ],
            $aggregation->toArray()
        );
    }

    public static function createDataProvider(): array
    {
        $ret = [];
        foreach (Metric::all() + Bucket::all() as $type) {
            $ret['type ' . $type] = [
                'field'   => 'my_field',
                'type'    => $type,
                'name'    => 'my_agg',
                'options' => [
                    'some_option' => 'has_a_value',
                ],
            ];
        }

        return $ret;
    }

    public function testCreateWithOptions(): void
    {
        $aggregation = Aggregation::create(
            'my_field',
            Metric::SUM,
            'my_agg',
            [
                'some_option' => 'has_a_value',
            ]
        );

        $this->assertEquals(
            [
                'my_agg' => [
                    'sum' => [
                        'field'       => 'my_field',
                        'some_option' => 'has_a_value',
                    ],
                ],
            ],
            $aggregation->toArray()
        );
    }

    public function testCreateWithoutName(): void
    {
        $aggregation = Aggregation::create('my_field', Metric::SUM);

        $this->assertNotNull($aggregation->getName());
    }

    public function testCreateWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type "my_made_up_type" given');

        Aggregation::create('foo', 'my_made_up_type');
    }

    public function testNestOne(): void
    {
        $aggregation = Aggregation::create('my_field', Bucket::TERMS, 'my_term_buckets')
            ->nest(Aggregation::create('my_field', Metric::CARDINALITY, 'term_cardinality'));

        $this->assertEquals(
            [
                'my_term_buckets' => [
                    'terms' => [
                        'field' => 'my_field',
                    ],
                    'aggs'  => [
                        'term_cardinality' => [
                            'cardinality' => [
                                'field' => 'my_field',
                            ],
                        ],
                    ],
                ],
            ],
            $aggregation->toArray()
        );
    }

    public function testNestMultiple(): void
    {
        $aggregation = Aggregation::create('my_field', Bucket::TERMS, 'my_term_buckets')
            ->nest(Aggregation::create('my_field', Metric::CARDINALITY, 'term_cardinality'))
            ->nest(Aggregation::create('my_field', Metric::VALUE_COUNT, 'term_value_count'));

        $this->assertEquals(
            [
                'my_term_buckets' => [
                    'terms' => [
                        'field' => 'my_field',
                    ],
                    'aggs'  => [
                        'term_cardinality' => [
                            'cardinality' => [
                                'field' => 'my_field',
                            ],
                        ],
                        'term_value_count' => [
                            'value_count' => [
                                'field' => 'my_field',
                            ],
                        ],
                    ],
                ],
            ],
            $aggregation->toArray()
        );
    }

    public function testCreateGlobalWithoutOptions(): void
    {
        $aggregation = Aggregation::createGlobal('my_global_agg');

        $this->assertEquals(
            json_encode(
                [
                    'my_global_agg' => [
                        'global' => new stdClass(),
                    ],
                ]
            ),
            json_encode($aggregation->toArray())
        );
    }

    public function testCreateGlobalWithOptions(): void
    {
        $aggregation = Aggregation::createGlobal('my_global_agg', ['my_option' => 'foobar']);

        $this->assertEquals(
            json_encode(
                [
                    'my_global_agg' => [
                        'global'    => new stdClass(),
                        'my_option' => 'foobar',
                    ],
                ]
            ),
            json_encode($aggregation->toArray())
        );
    }

    public function testNestOneInGlobal(): void
    {
        $aggregation = Aggregation::createGlobal('all_products')
            ->nest(Aggregation::create('price', Metric::AVG, 'avg_price'));

        $this->assertEquals(
            json_encode(
                [
                    'all_products' => [
                        'global' => new stdClass(),
                        'aggs'   => [
                            'avg_price' => [
                                'avg' => [
                                    'field' => 'price',
                                ],
                            ],
                        ],
                    ],
                ]
            ),
            json_encode($aggregation->toArray())
        );
    }

    public function testCreateFieldlessWithoutOptions(): void
    {
        $aggregation = Aggregation::createFieldless(Bucket::FILTERS, 'my_fieldless_agg');

        $this->assertEquals(
            [
                'my_fieldless_agg' => [
                    'filters' => [],
                ],
            ],
            $aggregation->toArray()
        );
    }

    public function testCreateFieldlessWithOptions(): void
    {
        $aggregation = Aggregation::createFieldless(
            Bucket::FILTERS,
            'my_fieldless_agg',
            ['other_bucket_key' => 'foobar', 'filters' => ['bucket_a' => ['term' => ['field' => 'field_a']]]]
        );

        $this->assertEquals(
            [
                'my_fieldless_agg' => [
                    'filters' => [
                        'other_bucket_key' => 'foobar',
                        'filters'          => ['bucket_a' => ['term' => ['field' => 'field_a']]],
                    ],
                ],
            ],
            $aggregation->toArray()
        );
    }

    public function testCreateAggregationWithRange(): void
    {
        $aggregation = Aggregation::create(
            'my_field',
            Metric::RANGE,
            'my_agg',
            ['ranges' => [['from' => 1, 'to' => 2]]]
        );

        $this->assertEquals([
            'my_agg' => [
                'range' => [
                    'field'       => 'my_field',
                    'ranges'      => [
                        ['from' => 1, 'to' => 2],
                    ],
                ],
            ],
        ], $aggregation->toArray());
    }
}
