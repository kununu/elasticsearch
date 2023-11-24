<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\GeoShape;
use Kununu\Elasticsearch\Query\Criteria\GeoShapeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GeoShapeTest extends TestCase
{
    protected GeoShapeInterface|MockObject $geoShape;

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
        $this->geoShape = $this->createMock(GeoShapeInterface::class);

        $this->geoShape
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);
    }
}
