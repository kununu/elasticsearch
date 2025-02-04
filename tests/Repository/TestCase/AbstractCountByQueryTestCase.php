<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractCountByQueryTestCase extends AbstractRepositoryTestCase
{
    #[DataProvider('queriesDataProvider')]
    public function testCountByQuery(QueryInterface $query): void
    {
        $this->client
            ->expects(self::once())
            ->method('count')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->willReturn(['count' => self::DOCUMENT_COUNT]);

        self::assertEquals(self::DOCUMENT_COUNT, $this->getRepository()->countByQuery($query));
    }

    public function testCountByQueryFails(): void
    {
        $this->client
            ->expects(self::once())
            ->method('count')
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->countByQuery(Query::create());
        } catch (ReadOperationException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
