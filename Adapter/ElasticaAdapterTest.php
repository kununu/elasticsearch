<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Manager;

use App\Services\Elasticsearch\Adapter\ElasticaAdapter;
use App\Services\Elasticsearch\Exception\InvalidQueryException;
use App\Services\Elasticsearch\Query\Query;
use App\Services\Elasticsearch\Query\QueryInterface;
use Elastica\Client;
use Elastica\Document;
use Elastica\Index;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Result;
use Elastica\ResultSet;
use Elastica\Type;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticaAdapterTest extends MockeryTestCase
{
    protected const INDEX = 'some_index';
    protected const TYPE = '_doc';
    protected const ID = 'can_be_anything';
    protected const DOCUMENT_COUNT = 42;

    /** @var \Elastica\Client|\Mockery\MockInterface */
    protected $clientMock;

    /** @var \Elastica\Index|\Mockery\MockInterface */
    protected $indexMock;

    /** @var \Elastica\Type|\Mockery\MockInterface */
    protected $typeMock;

    protected function setUp(): void
    {
        $this->clientMock = Mockery::mock(Client::class);
        $this->indexMock = Mockery::mock(Index::class);
        $this->typeMock = Mockery::mock(Type::class);

        $this->clientMock
            ->shouldReceive('getIndex')
            ->byDefault()
            ->once()
            ->with(self::INDEX)
            ->andReturn($this->indexMock);

        $this->indexMock
            ->shouldReceive('getType')
            ->byDefault()
            ->once()
            ->with(self::TYPE)
            ->andReturn($this->typeMock);
    }

    protected function getAdapter(): ElasticaAdapter
    {
        return new ElasticaAdapter($this->clientMock, self::INDEX, self::TYPE);
    }

    protected function getInvalidQueryObject()
    {
        return new class() implements QueryInterface
        {
            public function toArray(): array
            {
                return [];
            }

            public static function create($query)
            {
                return new self();
            }
        };
    }

    public function testIndex(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $documentMock = Mockery::mock(Document::class);

        $this->typeMock
            ->shouldReceive('createDocument')
            ->once()
            ->with(self::ID, $document)
            ->andReturn($documentMock);

        $this->typeMock
            ->shouldReceive('addDocument')
            ->once()
            ->with($documentMock)
            ->andReturn();

        $this->getAdapter()->index(
            self::ID,
            $document
        );
    }

    public function testDelete(): void
    {
        $this->typeMock
            ->shouldReceive('deleteById')
            ->once()
            ->with(self::ID)
            ->andReturn();

        $this->getAdapter()->delete(
            self::ID
        );
    }

    public function testDeleteIndex(): void
    {
        $this->indexMock
            ->shouldNotReceive('getType');

        $this->indexMock
            ->shouldReceive('delete')
            ->once()
            ->andReturn();

        $this->getAdapter()->deleteIndex();
    }

    /**
     * @return array
     */
    public function searchResultData(): array
    {
        return [
            'no results' => [
                'es_result' => [],
                'adapter_result' => [],
            ],
            'one result' => [
                'es_result' => [
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
                'adapter_result' => [
                    ['foo' => 'bar'],
                ],
            ],
            'two results' => [
                'es_result' => [
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
                            '_source' => [
                                'second' => 'result',
                                'with_more_than' => 'one field',
                            ],
                            '_id' => 'else',
                            '_index' => self::INDEX,
                            '_type' => self::TYPE,
                            '_score' => 0,
                        ]
                    ),
                ],
                'adapter_result' => [
                    [
                        'foo' => 'bar',
                    ],
                    [
                        'second' => 'result',
                        'with_more_than' => 'one field',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider searchResultData
     *
     * @param array $esResult
     * @param array $expectedEndResult
     */
    public function testSearchWithoutQuery(array $esResult, array $expectedEndResult): void
    {
        $resultSetMock = Mockery::mock(ResultSet::class);
        $resultSetMock
            ->shouldReceive('getResults')
            ->once()
            ->andReturn($esResult);

        $resultSetMock
            ->shouldReceive('getTotalHits')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->andReturn($resultSetMock);

        $result = $this->getAdapter()->search();

        $this->assertEquals($expectedEndResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
    }

    /**
     * @dataProvider searchResultData
     *
     * @param array $esResult
     * @param array $expectedEndResult
     */
    public function testSearchByQuery(array $esResult, array $expectedEndResult): void
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

        $resultSetMock
            ->shouldReceive('getTotalHits')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->with($query)
            ->andReturn($resultSetMock);

        $result = $this->getAdapter()->search($query);

        $this->assertEquals($expectedEndResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
    }

    public function testSearchWithInvalidQuery(): void
    {
        $this->expectException(InvalidQueryException::class);

        $this->getAdapter()->search($this->getInvalidQueryObject());
    }

    public function testCount(): void
    {
        $this->typeMock
            ->shouldReceive('count')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getAdapter()->count());
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

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getAdapter()->count($query));
    }

    public function testCountWithInvalidQuery(): void
    {
        $this->expectException(InvalidQueryException::class);

        $this->getAdapter()->count($this->getInvalidQueryObject());
    }

    public function testAggregate(): void
    {
        $resultSetMock = Mockery::mock(ResultSet::class);
        $resultSetMock
            ->shouldReceive('getAggregations')
            ->once()
            ->andReturn(['foo' => 'bar']);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->with(null)
            ->andReturn($resultSetMock);

        $this->assertEquals(['foo' => 'bar'], $this->getAdapter()->aggregate());
    }

    public function testAggregateByQuery(): void
    {
        $query = Query::create(
            (new BoolQuery())
                ->addMust((new Term())->setTerm('foo', 'bar'))
        );

        $resultSetMock = Mockery::mock(ResultSet::class);
        $resultSetMock
            ->shouldReceive('getAggregations')
            ->once()
            ->andReturn(['foo' => 'bar']);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->with($query)
            ->andReturn($resultSetMock);

        $this->assertEquals(['foo' => 'bar'], $this->getAdapter()->aggregate($query));
    }

    public function testAggregateWithInvalidQuery(): void
    {
        $this->expectException(InvalidQueryException::class);

        $this->getAdapter()->aggregate($this->getInvalidQueryObject());
    }
}
