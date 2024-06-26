<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query;

use Kununu\Elasticsearch\Query\RawQuery;
use Kununu\Elasticsearch\Query\SortOrder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RawQueryTest extends TestCase
{
    #[DataProvider('createDataProvider')]
    public function testCreate(array $rawQuery): void
    {
        $query = RawQuery::create($rawQuery);

        self::assertEquals($rawQuery, $query->toArray());
    }

    public static function createDataProvider(): array
    {
        return [
            'empty'     => [
                'rawQuery' => [],
            ],
            'non_empty' => [
                'rawQuery' => ['query' => ['term' => ['field' => 'value']]],
            ],
        ];
    }

    public function testCommonFunctionalityIsPreservedOnToArray(): void
    {
        $query = RawQuery::create(['query' => ['term' => ['field' => 'value']]])
            ->select(['field_a'])
            ->sort('field_a')
            ->skip(1)
            ->limit(10);

        self::assertEquals(
            [
                'query'   => ['term' => ['field' => 'value']],
                '_source' => ['field_a'],
                'size'    => 10,
                'from'    => 1,
                'sort'    => [
                    'field_a' => ['order' => SortOrder::ASC],
                ],
            ],
            $query->toArray()
        );
    }
}
