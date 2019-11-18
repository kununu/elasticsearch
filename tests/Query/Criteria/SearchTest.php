<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Search;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class SearchTest extends MockeryTestCase
{
    public function testCreateWithoutFields(): void
    {
        $this->expectExceptionMessage('No fields given');
        $this->expectException(InvalidArgumentException::class);

        Search::create([], 'foo');
    }

    public function testCreateWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown full text search type "bar" given');

        Search::create(['my_field'], 'foo', 'bar');
    }

    public function createData(): array
    {
        $ret = [];

        foreach (Search::all() as $type) {
            $ret[$type] = [
                'type' => $type,
            ];
        }

        return $ret;
    }

    /**
     * @dataProvider createData
     *
     * @param string $type
     */
    public function testCreate(string $type): void
    {
        $serialized = Search::create(['my_field'], 'i am looking for something', $type)->toArray();

        $this->assertNotEmpty($serialized);
        $this->assertArrayHasKey($type, $serialized);
    }
}
