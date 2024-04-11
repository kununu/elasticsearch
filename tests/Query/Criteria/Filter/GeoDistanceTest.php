<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\GeoDistance;
use Kununu\Elasticsearch\Query\Criteria\GeoDistanceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GeoDistanceTest extends TestCase
{
    protected MockObject&GeoDistanceInterface $geoDistance;

    public function testWithoutOptions(): void
    {
        self::assertEquals(
            [
                'geo_distance' => [
                    'distance' => '42km',
                    'field_a'  => [0, 0],
                ],
            ],
            GeoDistance::asArray('field_a', $this->geoDistance)
        );
    }

    public function testWithOptions(): void
    {
        self::assertEquals(
            [
                'geo_distance' => [
                    'distance'      => '42km',
                    'field_a'       => [0, 0],
                    'distance_type' => 'plane',
                ],
            ],
            GeoDistance::asArray('field_a', $this->geoDistance, ['distance_type' => 'plane'])
        );
    }

    protected function setUp(): void
    {
        $this->geoDistance = $this->createMock(GeoDistanceInterface::class);

        $this->geoDistance
            ->expects(self::once())
            ->method('getDistance')
            ->willReturn('42km');

        $this->geoDistance
            ->expects(self::once())
            ->method('getLocation')
            ->willReturn([0, 0]);
    }
}
