<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Manager;

use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Query\Query;
use App\Tests\Unit\Services\Elasticsearch\ElasticsearchManagerTestTrait;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticsearchManagerTest extends MockeryTestCase
{
    use ElasticsearchManagerTestTrait;

    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';
    protected const ID = 'can_be_anything';
    protected const DOCUMENT_COUNT = 42;

    public function testSave(): void
    {
        $data = [
            'whatever' => 'just some data',
        ];

        $this->elasticaAdapterMock
            ->shouldReceive('index')
            ->once()
            ->with(self::ID, $data);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->save(
            self::ID,
            $data
        );
    }

    public function testSaveFails(): void
    {
        $data = [
            'foo' => 'bar',
        ];

        $this->elasticaAdapterMock
            ->shouldReceive('index')
            ->once()
            ->with(self::ID, $data)
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->save(
            self::ID,
            $data
        );
    }

    public function testDelete(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('delete')
            ->once()
            ->with(self::ID)
            ->andReturn();

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->delete(
            self::ID
        );
    }

    public function testDeleteFails(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('delete')
            ->once()
            ->with(self::ID)
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->delete(
            self::ID
        );
    }

    public function testDeleteIndex(): void
    {
        $this->elasticaAdapterMock->shouldReceive('deleteIndex')
            ->once()
            ->andReturn();

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->deleteIndex();
    }

    public function testDeleteIndexFails(): void
    {
        $this->elasticaAdapterMock->shouldReceive('deleteIndex')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->deleteIndex();
    }

    public function testFindByQuery(): void
    {
        $query = Query::create();

        $this->elasticaAdapterMock
            ->shouldReceive('search')
            ->once()
            ->with($query);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->findByQuery($query);
    }

    public function testFindByQueryFails(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->findByQuery(Query::create());
    }

    public function testCount(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('count')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getManager()->count());
    }

    public function testCountFails(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('count')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->count();
    }

    public function testCountByQuery(): void
    {
        $query = Query::create(
            (new BoolQuery())
                ->addMust((new Term())->setTerm('foo', 'bar'))
        );

        $this->elasticaAdapterMock
            ->shouldReceive('count')
            ->once()
            ->with($query)
            ->andReturn(self::DOCUMENT_COUNT);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getManager()->countByQuery($query));
    }

    public function testCountByQueryFails(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('count')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->countByQuery(Query::create());
    }

    public function testUpdateByQuery(): void
    {
        $query = Query::create(
            (new BoolQuery())
                ->addMust((new Term())->setTerm('foo', 'bar'))
        );

        $updateScript = [
            'lang' => 'painless',
            'source' => 'ctx._source.dimensions_completed=4',
        ];

        $responseBody = [
            'took' => 147,
            'timed_out' => false,
            'total' => 5,
            'updated' => 5,
            'deleted' => 0,
            'batches' => 1,
            'version_conflicts' => 0,
            'noops' => 0,
            'retries' => [
                'bulk' => 0,
                'search' => 0,
            ],
            'throttled_millis' => 0,
            'requests_per_second' => -1.0,
            'throttled_until_millis' => 0,
            'failures' => [],
        ];

        $this->elasticaAdapterMock
            ->shouldReceive('update')
            ->once()
            ->with($query, $updateScript)
            ->andReturn($responseBody);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($responseBody, $this->getManager()->updateByQuery($query, $updateScript));
    }

    public function testUpdateByQueryFails(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('update')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->updateByQuery(Query::create(), []);
    }

    /**
     * @return \App\Services\Elasticsearch\Manager\ElasticsearchManagerInterface
     */
    private function getManager(): \App\Services\Elasticsearch\Manager\ElasticsearchManagerInterface
    {
        return new \App\Services\Elasticsearch\Manager\ElasticsearchManager(
            $this->elasticaAdapterMock,
            $this->loggerMock
        );
    }
}
