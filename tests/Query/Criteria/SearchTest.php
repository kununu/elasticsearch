<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Search;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SearchTest extends TestCase
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

    #[DataProvider('createDataProvider')]
    public function testCreate(string $type): void
    {
        $serialized = Search::create(['my_field'], 'i am looking for something', $type)->toArray();

        self::assertNotEmpty($serialized);
        self::assertArrayHasKey($type, $serialized);
    }

    public static function createDataProvider(): array
    {
        $ret = [];

        foreach (Search::all() as $type) {
            $ret[$type] = [
                'type' => $type,
            ];
        }

        return $ret;
    }
}
