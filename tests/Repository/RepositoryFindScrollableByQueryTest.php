<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Query\Query;

final class RepositoryFindScrollableByQueryTest extends AbstractRepositoryTestCase
{
    /** @dataProvider searchResultDataProvider */
    public function testFindScrollableByQueryCanOverrideScrollContextKeepalive(array $esResult, mixed $endResult): void
    {
        $query = Query::create();
        $keepalive = '10m';

        $rawParams = [
            'index'  => self::INDEX['read'],
            'body'   => $query->toArray(),
            'scroll' => $keepalive,
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($esResult);

        $result = $this->getRepository()->findScrollableByQuery($query, $keepalive);

        $this->assertEquals($endResult, $result->asArray());
    }
}
