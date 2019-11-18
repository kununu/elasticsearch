<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\GeoDistance;
use Kununu\Elasticsearch\Query\Criteria\GeoDistanceInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class GeoDistanceTest extends MockeryTestCase
{
    /**
     * @var \Kununu\Elasticsearch\Query\Criteria\GeoDistanceInterface|\Mockery\MockInterface
     */
    protected $geoDistance;

    public function setUp(): void
    {
        $this->geoDistance = \Mockery::mock(GeoDistanceInterface::class);

        $this->geoDistance
            ->shouldReceive('getDistance')
            ->once()
            ->andReturn('42km');

        $this->geoDistance
            ->shouldReceive('getLocation')
            ->once()
            ->andReturn([0, 0]);
    }

    public function testWithoutOptions(): void
    {
        $this->assertEquals(
            [
                'geo_distance' => [
                    'distance' => '42km',
                    'field_a' => [0, 0],
                ],
            ],
            GeoDistance::asArray('field_a', $this->geoDistance)
        );
    }

    public function testWithOptions(): void
    {
        $this->assertEquals(
            [
                'geo_distance' => [
                    'distance' => '42km',
                    'field_a' => [0, 0],
                    'distance_type' => 'plane',
                ],
            ],
            GeoDistance::asArray('field_a', $this->geoDistance, ['distance_type' => 'plane'])
        );
    }
}
