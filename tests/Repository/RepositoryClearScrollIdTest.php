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
            ->expects(self::once())
            ->method('clearScroll')
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->clearScrollId($scrollId);
    }

    public function testClearScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->expects(self::once())
            ->method('clearScroll')
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                self::ERROR_PREFIX . self::ERROR_MESSAGE
            );

        try {
            $this->getRepository()->clearScrollId($scrollId);
        } catch (RepositoryException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
        }
    }
}
