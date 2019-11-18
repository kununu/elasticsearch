<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\GeoShape;
use Kununu\Elasticsearch\Query\Criteria\GeoShapeInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class GeoShapeTest extends MockeryTestCase
{
    /**
     * @var \Kununu\Elasticsearch\Query\Criteria\GeoShapeInterface|\Mockery\MockInterface
     */
    protected $geoShape;

    public function setUp(): void
    {
        $this->geoShape = \Mockery::mock(GeoShapeInterface::class);

        $this->geoShape
            ->shouldReceive('toArray')
            ->once()
            ->andReturn([]);
    }

    public function testWithoutOptions(): void
    {
        $this->assertEquals(
            [
                'geo_shape' => [
                    'field_a' => [
                        'shape' => [],
                    ],
                ],
            ],
            GeoShape::asArray('field_a', $this->geoShape)
        );
    }

    public function testWithOptions(): void
    {
        $this->assertEquals(
            [
                'geo_shape' => [
                    'field_a' => [
                        'shape' => [],
                        'ignore_unmapped' => true,
                    ],
                ],
            ],
            GeoShape::asArray('field_a', $this->geoShape, ['ignore_unmapped' => true])
        );
    }
}
