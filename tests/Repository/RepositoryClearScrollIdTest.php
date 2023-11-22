<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\RepositoryException;

final class RepositoryClearScrollIdTest extends AbstractRepositoryTestCase
{
    public function testClearScrollId(): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->shouldReceive('clearScroll')
            ->once()
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository()->clearScrollId($scrollId);
    }

    public function testClearScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->shouldReceive('clearScroll')
            ->once()
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ])
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                self::ERROR_PREFIX . self::ERROR_MESSAGE
            );

        try {
            $this->getRepository()->clearScrollId($scrollId);
        } catch (RepositoryException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
        }
    }
}
