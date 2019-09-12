<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use App\Services\Elasticsearch\ElasticsearchManager;
use App\Services\Elasticsearch\ElasticsearchManagerInterface;
use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Exception\ManagerConfigurationException;
use Elastica\Document;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Result;
use Elastica\ResultSet;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticsearchManagerTest extends MockeryTestCase
{
    use ElasticsearchManagerTestTrait;

    protected const INDEX = 'some_index';
    protected const TYPE = '_doc';
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';
    protected const ID = 'can_be_anything';
    protected const DOCUMENT_COUNT = 42;

    public function testNoIndexDefined(): void
    {
        $this->elasticsearchClientMock
            ->shouldNotReceive('getIndex');

        $this->indexMock
            ->shouldNotReceive('getType');

        $this->expectException(ManagerConfigurationException::class);

        $foo = new ElasticsearchManager(
            $this->elasticsearchClientMock,
            $this->loggerMock,
            ''
        );
    }

    public function testSave(): void
    {
        $data = [
            'whatever' => 'just some data',
        ];

        $documentMock = Mockery::mock(Document::class);

        $this->typeMock
            ->shouldReceive('createDocument')
            ->once()
            ->with(self::ID, $data)
            ->andReturn($documentMock);

        $this->typeMock
            ->shouldReceive('addDocument')
            ->once()
            ->with($documentMock)
            ->andReturn();

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

        $documentMock = Mockery::mock(Document::class);

        $this->typeMock
            ->shouldReceive('createDocument')
            ->once()
            ->with(self::ID, $data)
            ->andReturn($documentMock);

        $this->typeMock
            ->shouldReceive('addDocument')
            ->once()
            ->with($documentMock)
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
        $this->typeMock
            ->shouldReceive('deleteById')
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
        $this->typeMock
            ->shouldReceive('deleteById')
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
        $this->indexMock
            ->shouldNotReceive('getType');

        $this->indexMock->shouldReceive('delete')
            ->once()
            ->andReturn();

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->deleteIndex();
    }

    public function testDeleteIndexFails(): void
    {
        $this->indexMock
            ->shouldNotReceive('getType');

        $this->indexMock->shouldReceive('delete')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->deleteIndex();
    }

    public function testFindAll(): void
    {
        $resultSetMock = Mockery::mock(ResultSet::class);
        $resultSetMock
            ->shouldReceive('getResults')
            ->once()
            ->andReturn([]);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->andReturn(
                $resultSetMock
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $response = $this->getManager()->findAll();

        $this->assertArrayNotHasKey('hits', $response);
    }

    public function testFindAllFails(): void
    {
        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->findAll();
    }

    /**
     * @return array
     */
    public function findByQueryData(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [
                    new Result(
                        [
                            '_source' => ['foo' => 'bar'],
                            '_id' => 'something',
                            '_index' => self::INDEX,
                            '_type' => self::TYPE,
                            '_score' => 0,
                        ]
                    ),
                ],
                [
                    ['foo' => 'bar'],
                ],
            ],
            [
                [
                    new Result(
                        [
                            '_source' => ['foo' => 'bar'],
                            '_id' => 'something',
                            '_index' => self::INDEX,
                            '_type' => self::TYPE,
                            '_score' => 1,
                        ]
                    ),
                    new Result(
                        [
                            '_source' => ['second' => 'result'],
                            '_id' => 'else',
                            '_index' => self::INDEX,
                            '_type' => self::TYPE,
                            '_score' => 0,
                        ]
                    ),
                ],
                [
                    [
                        'foo' => 'bar',
                    ],
                    [
                        'second' => 'result',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider findByQueryData
     *
     * @param array $esResult
     * @param array $endResult
     */
    public function testFindByQuery(array $esResult, array $endResult): void
    {
        $query = Query::create(
            (new BoolQuery())
                ->addMust((new Term())->setTerm('foo', 'bar'))
        );

        $resultSetMock = Mockery::mock(ResultSet::class);
        $resultSetMock
            ->shouldReceive('getResults')
            ->once()
            ->andReturn($esResult);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->with($query)
            ->andReturn($resultSetMock);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getManager()->findByQuery($query);

        $this->assertEquals($endResult, $result);
    }

    public function testFindByQueryFails(): void
    {
        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->findByQuery(Query::create(null));
    }

    public function testCount(): void
    {
        $this->typeMock
            ->shouldReceive('count')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getManager()->count());
    }

    public function testCountFails(): void
    {
        $this->typeMock
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

        $this->typeMock
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
        $this->typeMock
            ->shouldReceive('count')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);

        $this->getManager()->countByQuery(Query::create(null));
    }

    /**
     * @return \App\Services\Elasticsearch\ElasticsearchManagerInterface
     */
    private function getManager(): ElasticsearchManagerInterface
    {
        return new ElasticsearchManager($this->elasticsearchClientMock, $this->loggerMock, self::INDEX);
    }
}
