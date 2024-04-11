<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Query\Query;
use PHPUnit\Framework\Attributes\DataProvider;

final class RepositoryFindScrollableByQueryTest extends AbstractRepositoryTestCase
{
    #[DataProvider('searchResultDataProvider')]
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
            ->expects(self::once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($esResult);

        $result = $this->getRepository()->findScrollableByQuery($query, $keepalive);

        self::assertEquals($endResult, $result->asArray());
    }
}
