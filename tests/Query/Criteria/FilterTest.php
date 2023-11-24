<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\GeoDistanceInterface;
use Kununu\Elasticsearch\Query\Criteria\GeoShapeInterface;
use Kununu\Elasticsearch\Query\Criteria\Operator;
use PHPUnit\Framework\TestCase;

final class FilterTest extends TestCase
{
    public function testCreateWithInvalidOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown operator "bar" given');

        Filter::create('my_field', 'foo', 'bar');
    }

    /** @dataProvider createDataProvider */
    public function testCreate(string $operator, mixed $value): void
    {
        $serialized = Filter::create('my_field', $value, $operator)->toArray();

        $this->assertNotEmpty($serialized);
    }

    public function createDataProvider(): array
    {
        $ret = [];

        foreach (Operator::all() as $operator) {
            if (!in_array($operator, [Operator::GEO_DISTANCE, Operator::GEO_SHAPE])) {
                $value = match ($operator) {
                    Operator::TERMS  => ['a', 'b'],
                    Operator::EXISTS => true,
                    default          => 'foo',
                };

                $ret[$operator] = [
                    'operator' => $operator,
                    'value'    => $value,
                ];
            }
        }

        return $ret;
    }

    public function testCreateWithoutOperatorCreatesTermFilter(): void
    {
        $serialized = Filter::create('my_field', 'value')->toArray();

        $this->assertNotEmpty($serialized);
        $this->assertArrayHasKey('term', $serialized);
    }

    public function testCreateGeoShape(): void
    {
        $geoShape = $this->createMock(GeoShapeInterface::class);

        $geoShape
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $serialized = Filter::create('my_field', $geoShape, Operator::GEO_SHAPE)->toArray();

        $this->assertNotEmpty($serialized);
        $this->assertArrayHasKey('geo_shape', $serialized);
    }

    public function testCreateGeoDistance(): void
    {
        $geoDistance = $this->createMock(GeoDistanceInterface::class);

        $geoDistance
            ->expects($this->once())
            ->method('getDistance')
            ->willReturn('42km');

        $geoDistance
            ->expects($this->once())
            ->method('getLocation')
            ->willReturn([0, 0]);

        $serialized = Filter::create('my_field', $geoDistance, Operator::GEO_DISTANCE)->toArray();

        $this->assertNotEmpty($serialized);
        $this->assertArrayHasKey('geo_distance', $serialized);
    }
}
