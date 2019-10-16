<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Query\Aggregation;
use App\Services\Elasticsearch\Query\Aggregation\Bucket;
use App\Services\Elasticsearch\Query\Aggregation\Metric;
use InvalidArgumentException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class AggregationTest extends MockeryTestCase
{
    /**
     * @return array
     */
    public function createData(): array
    {
        $ret = [];
        foreach (Metric::all() + Bucket::all() as $type) {
            $ret['type ' . $type] = [
                'field' => 'my_field',
                'type' => $type,
                'name' => 'my_agg',
                'options' => [
                    'some_option' => 'has_a_value',
                ],
            ];
        }

        return $ret;
    }

    /**
     * @dataProvider createData
     *
     * @param string $field
     * @param string $type
     * @param string $name
     */
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
                        'field' => 'my_field',
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
                    'aggs' => [
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
                    'aggs' => [
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
}
