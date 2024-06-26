<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\WriteOperationException;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;

final class RepositoryUpdateByQueryTest extends AbstractRepositoryTestCase
{
    public function testUpdateByQuery(): void
    {
        $query = Query::create(
            Filter::create('foo', 'bar')
        );

        $updateScript = [
            'lang'   => 'painless',
            'source' => 'ctx._source.dimensions_completed=4',
        ];

        $responseBody = [
            'took'                   => 147,
            'timed_out'              => false,
            'total'                  => 5,
            'updated'                => 5,
            'deleted'                => 0,
            'batches'                => 1,
            'version_conflicts'      => 0,
            'noops'                  => 0,
            'retries'                => [
                'bulk'   => 0,
                'search' => 0,
            ],
            'throttled_millis'       => 0,
            'requests_per_second'    => -1.0,
            'throttled_until_millis' => 0,
            'failures'               => [],
        ];

        $this->clientMock
            ->expects(self::once())
            ->method('updateByQuery')
            ->with([
                'index' => self::INDEX['write'],
                'body'  => array_merge(
                    $query->toArray(),
                    [
                        'script' => [
                            'lang'   => 'painless',
                            'source' => 'ctx._source.dimensions_completed=4',
                            'params' => [],
                        ],
                    ]
                ),
            ])
            ->willReturn($responseBody);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals($responseBody, $this->getRepository()->updateByQuery($query, $updateScript));
    }

    public function testUpdateByQueryWithForcedRefresh(): void
    {
        $query = Query::create(
            Filter::create('foo', 'bar')
        );

        $updateScript = [
            'lang'   => 'painless',
            'source' => 'ctx._source.dimensions_completed=4',
        ];

        $responseBody = [
            'took'                   => 147,
            'timed_out'              => false,
            'total'                  => 5,
            'updated'                => 5,
            'deleted'                => 0,
            'batches'                => 1,
            'version_conflicts'      => 0,
            'noops'                  => 0,
            'retries'                => [
                'bulk'   => 0,
                'search' => 0,
            ],
            'throttled_millis'       => 0,
            'requests_per_second'    => -1.0,
            'throttled_until_millis' => 0,
            'failures'               => [],
        ];

        $this->clientMock
            ->expects(self::once())
            ->method('updateByQuery')
            ->with([
                'index'   => self::INDEX['write'],
                'body'    => array_merge(
                    $query->toArray(),
                    [
                        'script' => [
                            'lang'   => 'painless',
                            'source' => 'ctx._source.dimensions_completed=4',
                            'params' => [],
                        ],
                    ]
                ),
                'refresh' => true,
            ])
            ->willReturn($responseBody);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $repository = $this->getRepository(['force_refresh_on_write' => true]);

        self::assertEquals($responseBody, $repository->updateByQuery($query, $updateScript));
    }

    public function testUpdateByQueryFails(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('updateByQuery')
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->updateByQuery(Query::create(), ['script' => []]);
        } catch (WriteOperationException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
        }
    }
}
