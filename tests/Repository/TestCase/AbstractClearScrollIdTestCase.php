<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\RepositoryException;

abstract class AbstractClearScrollIdTestCase extends AbstractRepositoryTestCase
{
    public function testClearScrollId(): void
    {
        $scrollId = 'foobar';

        $this->client
            ->expects(self::once())
            ->method('clearScroll')
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ])
            ->willReturn([]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->clearScrollId($scrollId);
    }

    public function testClearScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->client
            ->expects(self::once())
            ->method('clearScroll')
            ->with([
                'body' => [
                    'scroll_id' => $scrollId,
                ],
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->clearScrollId($scrollId);
        } catch (RepositoryException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
        }
    }
}
