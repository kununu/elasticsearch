<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Query;

abstract class AbstractCountTestCase extends AbstractRepositoryTestCase
{
    public function testCount(): void
    {
        $query = Query::create();

        $this->client
            ->expects(self::once())
            ->method('count')
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->willReturn(['count' => self::DOCUMENT_COUNT]);

        self::assertEquals(self::DOCUMENT_COUNT, $this->getRepository()->count());
    }

    public function testCountFails(): void
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
            $this->getRepository()->count();
        } catch (ReadOperationException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }
}
