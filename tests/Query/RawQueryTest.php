<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query;

use Kununu\Elasticsearch\Query\RawQuery;
use Kununu\Elasticsearch\Query\SortOrder;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class RawQueryTest extends MockeryTestCase
{
    /**
     * @return array
     */
    public function createData(): array
    {
        return [
            'empty' => [
                'rawQuery' => [],
            ],
            'non-empty' => [
                'rawQuery' => ['query' => ['term' => ['field' => 'value']]],
            ],
        ];
    }

    /**
     * @dataProvider createData
     *
     * @param array $rawQuery
     */
    public function testCreate(array $rawQuery): void
    {
        $query = RawQuery::create($rawQuery);

        $this->assertEquals($rawQuery, $query->toArray());
    }

    public function testCommonFunctionalityIsPreservedOnToArray(): void
    {
        $query = RawQuery::create(['query' => ['term' => ['field' => 'value']]])
            ->select(['field_a'])
            ->sort('field_a')
            ->skip(1)
            ->limit(10);

        $this->assertEquals(
            [
                'query' => ['term' => ['field' => 'value']],
                '_source' => ['field_a'],
                'size' => 10,
                'from' => 1,
                'sort' => [
                    'field_a' => ['order' => SortOrder::ASC],
                ],
            ],
            $query->toArray()
        );
    }
}
