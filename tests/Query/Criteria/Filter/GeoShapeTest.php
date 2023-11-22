<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\GeoShape;
use Kununu\Elasticsearch\Query\Criteria\GeoShapeInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class GeoShapeTest extends MockeryTestCase
{
    protected MockInterface|GeoShapeInterface $geoShape;

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
                        'shape'           => [],
                        'ignore_unmapped' => true,
                    ],
                ],
            ],
            GeoShape::asArray('field_a', $this->geoShape, ['ignore_unmapped' => true])
        );
    }

    protected function setUp(): void
    {
        $this->geoShape = Mockery::mock(GeoShapeInterface::class);

        $this->geoShape
            ->shouldReceive('toArray')
            ->once()
            ->andReturn([]);
    }
}
