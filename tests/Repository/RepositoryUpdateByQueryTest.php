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
            ->shouldReceive('updateByQuery')
            ->once()
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
            ->andReturn($responseBody);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($responseBody, $this->getRepository()->updateByQuery($query, $updateScript));
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
            ->shouldReceive('updateByQuery')
            ->once()
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
            ->andReturn($responseBody);

        $this->loggerMock
            ->shouldNotReceive('error');

        $repository = $this->getRepository(['force_refresh_on_write' => true]);

        $this->assertEquals($responseBody, $repository->updateByQuery($query, $updateScript));
    }

    public function testUpdateByQueryFails(): void
    {
        $this->clientMock
            ->shouldReceive('updateByQuery')
            ->once()
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->updateByQuery(Query::create(), ['script' => []]);
        } catch (WriteOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
        }
    }
}
