<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Kununu\Elasticsearch\Query\Query;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractFindScrollableByQueryTestCase extends AbstractRepositoryTestCase
{
    #[DataProvider('searchResultDataProvider')]
    public function testFindScrollableByQueryCanOverrideScrollContextKeepalive(array $result, mixed $endResult): void
    {
        $query = Query::create();
        $keepalive = '10m';

        $rawParams = [
            'index'  => self::INDEX['read'],
            'body'   => $query->toArray(),
            'scroll' => $keepalive,
        ];

        $this->client
            ->expects(self::once())
            ->method('search')
            ->with($rawParams)
            ->willReturn($result);

        $result = $this->getRepository()->findScrollableByQuery($query, $keepalive);

        self::assertEquals($endResult, $result->asArray());
    }
}
