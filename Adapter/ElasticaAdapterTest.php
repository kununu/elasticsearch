<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Manager;

use App\Services\Elasticsearch\Adapter\ElasticaAdapter;
use App\Services\Elasticsearch\Query\Query;
use App\Tests\Unit\Services\Elasticsearch\ElasticsearchManagerTestTrait;
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
    use ElasticsearchManagerTestTrait;

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

    public function donttestVerifyElasticaQueryObject(): void
    {
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
                [
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
     * @param array $endResult
     */
    public function testSearchWithoutQuery(array $esResult, array $endResult): void
    {
        $resultSetMock = Mockery::mock(ResultSet::class);
        $resultSetMock
            ->shouldReceive('getResults')
            ->once()
            ->andReturn($esResult);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->andReturn($resultSetMock);

        $result = $this->getAdapter()->search();

        $this->assertEquals($endResult, $result);
    }

    /**
     * @dataProvider searchResultData
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

        $result = $this->getAdapter()->search($query);

        $this->assertEquals($endResult, $result);
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
}
