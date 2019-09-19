<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Repository;

use App\Services\Elasticsearch\Adapter\AdapterInterface;
use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Query\Query;
use App\Services\Elasticsearch\Repository\ElasticsearchRepository;
use App\Services\Elasticsearch\Repository\ElasticsearchRepositoryInterface;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;

/**
 * @group unit
 */
class ElasticsearchRepositoryTest extends MockeryTestCase
{
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';
    protected const ID = 'can_be_anything';
    protected const DOCUMENT_COUNT = 42;

    /** @var \App\Services\Elasticsearch\Adapter\AdapterInterface|\Mockery\MockInterface */
    protected $adapterMock;

    /** @var \Psr\Log\LoggerInterface|\Mockery\MockInterface */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->adapterMock = Mockery::mock(AdapterInterface::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
    }

    public function testSave(): void
    {
        $data = [
            'whatever' => 'just some data',
        ];

        $this->adapterMock
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

        $this->adapterMock
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
        $this->adapterMock
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
        $this->adapterMock
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
        $indexName = 'my_index';

        $this->adapterMock->shouldReceive('deleteIndex')
            ->once()
            ->with($indexName)
            ->andReturn();

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->deleteIndex($indexName);
    }

    public function testDeleteIndexFails(): void
    {
        $indexName = 'my_index';

        $this->adapterMock->shouldReceive('deleteIndex')
            ->once()
            ->with($indexName)
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->deleteIndex($indexName);
    }

    public function testFindByQuery(): void
    {
        $query = Query::create();

        $this->adapterMock
            ->shouldReceive('search')
            ->once()
            ->with($query);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->findByQuery($query);
    }

    public function testFindByQueryFails(): void
    {
        $this->adapterMock
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
        $this->adapterMock
            ->shouldReceive('count')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getManager()->count());
    }

    public function testCountFails(): void
    {
        $this->adapterMock
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

        $this->adapterMock
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
        $this->adapterMock
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

        $this->adapterMock
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
        $this->adapterMock
            ->shouldReceive('update')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->updateByQuery(Query::create(), []);
    }

    public function testFindScrollableByQuery(): void
    {
        $query = Query::create();

        $this->adapterMock
            ->shouldReceive('search')
            ->once()
            ->with($query, true);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->findScrollableByQuery($query);
    }

    public function testFindScrollableByQueryFails(): void
    {
        $query = Query::create();

        $this->adapterMock
            ->shouldReceive('search')
            ->once()
            ->with($query, true)
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->findScrollableByQuery($query);
    }

    public function testFindByScrollId(): void
    {
        $scrollId = 'foobar';

        $this->adapterMock
            ->shouldReceive('scroll')
            ->once()
            ->with($scrollId);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->findByScrollId($scrollId);
    }

    public function testFindByScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->adapterMock
            ->shouldReceive('scroll')
            ->once()
            ->with($scrollId)
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->findByScrollId($scrollId);
    }

    /**
     * @return \App\Services\Elasticsearch\Repository\ElasticsearchRepositoryInterface
     */
    private function getManager(): ElasticsearchRepositoryInterface
    {
        return new ElasticsearchRepository(
            $this->adapterMock,
            $this->loggerMock
        );
    }
}
