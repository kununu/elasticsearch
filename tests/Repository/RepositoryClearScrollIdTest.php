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
            ->expects($this->once())
            ->method('clearScroll')
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ]);

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $this->getRepository()->clearScrollId($scrollId);
    }

    public function testClearScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->expects($this->once())
            ->method('clearScroll')
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
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
