<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Query\RawQuery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class RawQueryTest extends MockeryTestCase
{
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
}
