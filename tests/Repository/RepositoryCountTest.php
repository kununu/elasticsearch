<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Query\Query;

final class RepositoryCountTest extends AbstractRepositoryTestCase
{
    public function testCount(): void
    {
        $query = Query::create();

        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'body'  => $query->toArray(),
            ])
            ->andReturn(['count' => self::DOCUMENT_COUNT]);

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getRepository()->count());
    }

    public function testCountFails(): void
    {
        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->count();
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
    }
}
