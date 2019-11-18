<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Repository;

use App\Services\Elasticsearch\Adapter\ElasticaAdapter;
use App\Services\Elasticsearch\Query\Criteria\Filter;
use App\Services\Elasticsearch\Query\ElasticaQuery;
use App\Services\Elasticsearch\Query\Query;
use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Query\RawQuery;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\NotFoundException;
use Elastica\Index;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Response;
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
    protected const UPDATE_RESPONSE_BODY = [
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
    protected const SCROLL_ID = 'DnF1ZXJ5VGhlbkZldGNoBQAAAAAAAAFbFkJVNEdjZWVjU';

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

    /**
     * @return \App\Services\Elasticsearch\Adapter\ElasticaAdapter
     */
    protected function getAdapter(): ElasticaAdapter
    {
        return new ElasticaAdapter($this->clientMock, self::INDEX, self::TYPE);
    }

    /**
     * @return \App\Services\Elasticsearch\Query\QueryInterface|\Mockery\MockInterface
     */
    protected function getInvalidQueryObject()
    {
        return Mockery::mock(QueryInterface::class);
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
            ->with()
            ->andReturn();

        $this->getAdapter()->deleteIndex(self::INDEX);
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
     * @return array
     */
    public function queriesData(): array
    {
        return [
            'empty kununu query' => [
                'query' => Query::create(),
            ],
            'some kununu term query' => [
                'query' => Query::create(
                    Filter::create('foo', 'bar')
                ),
            ],
            'empty elastica query' => [
                'query' => ElasticaQuery::create(),
            ],
            'some elastica term query' => [
                'query' => ElasticaQuery::create(
                    (new BoolQuery())
                        ->addMust((new Term())->setTerm('foo', 'bar'))
                ),
            ],
            'empty raw query' => [
                'query' => RawQuery::create(),
            ],
            'some raw term query' => [
                'query' => RawQuery::create(['query' => ['bool' => ['must' => [['term' => ['foo' => 'bar']]]]]]),
            ],
        ];
    }

    /**
     * @return array
     */
    public function queryAndSearchResultData(): array
    {
        $queryVariations = $this->queriesData();
        $resultsVariations = $this->searchResultData();

        $allVariations = [];
        foreach ($queryVariations as $queryName => $queryVariation) {
            foreach ($resultsVariations as $resultsName => $resultsVariation) {
                $allVariations[$queryName . ', ' . $resultsName] = array_merge($queryVariation, $resultsVariation);
            }
        }

        return $allVariations;
    }

    /**
     * @dataProvider queryAndSearchResultData
     *
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $esResult
     * @param array                                            $expectedEndResult
     */
    public function testSearchByQuery(QueryInterface $query, array $esResult, array $expectedEndResult): void
    {
        $this->doTestSearch($esResult, $expectedEndResult, $query);
    }

    /**
     * @dataProvider queryAndSearchResultData
     *
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $esResult
     * @param array                                            $expectedEndResult
     */
    public function testSearchScrollableByQuery(QueryInterface $query, array $esResult, array $expectedEndResult): void
    {
        $this->doTestSearch($esResult, $expectedEndResult, $query, true);
    }

    /**
     * @param array                                            $esResult
     * @param array                                            $expectedEndResult
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param bool                                             $scroll
     */
    protected function doTestSearch(
        array $esResult,
        array $expectedEndResult,
        QueryInterface $query,
        bool $scroll = false
    ): void {
        $resultSetMock = Mockery::mock(ResultSet::class);
        $resultSetMock
            ->shouldReceive('getResults')
            ->once()
            ->andReturn($esResult);

        $resultSetMock
            ->shouldReceive('getTotalHits')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $responseMock = Mockery::mock(Response::class);

        if ($scroll) {
            $responseMock
                ->shouldReceive('getScrollId')
                ->once()
                ->andReturn(self::SCROLL_ID);
        } else {
            $responseMock
                ->shouldReceive('getScrollId')
                ->once()
                ->andThrow(NotFoundException::class);
        }

        $resultSetMock
            ->shouldReceive('getResponse')
            ->once()
            ->andReturn($responseMock);

        $options = $scroll
            ? ['scroll' => ElasticaAdapter::SCROLL_CONTEXT_KEEPALIVE]
            : [];

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->with(
                Mockery::on(
                    function ($argument) use ($query) {
                        return $argument === $query || $argument === $query->toArray();
                    }
                ),
                $options
            )
            ->andReturn($resultSetMock);

        $result = $this->getAdapter()->search($query, $scroll);

        $this->assertEquals($expectedEndResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            $this->assertNull($result->getScrollId());
        }
    }

    /**
     * @dataProvider queriesData
     *
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     */
    public function testCount(QueryInterface $query): void
    {
        $this->typeMock
            ->shouldReceive('count')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getAdapter()->count($query));
    }

    /**
     * @dataProvider queriesData
     *
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     */
    public function testAggregate(QueryInterface $query): void
    {
        $resultSetMock = Mockery::mock(ResultSet::class);
        $resultSetMock
            ->shouldReceive('getResults')
            ->once()
            ->andReturn([]);

        $resultSetMock
            ->shouldReceive('getTotalHits')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $responseMock = Mockery::mock(Response::class);

        $responseMock
            ->shouldReceive('getScrollId')
            ->once()
            ->andThrow(NotFoundException::class);

        $resultSetMock
            ->shouldReceive('getResponse')
            ->once()
            ->andReturn($responseMock);

        $resultSetMock
            ->shouldReceive('getAggregations')
            ->once()
            ->andReturn(['my_aggregation' => ['value' => 0.1]]);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->with(
                Mockery::on(
                    function ($argument) use ($query) {
                        return $argument === $query || $argument === $query->toArray();
                    }
                )
            )
            ->andReturn($resultSetMock);

        $aggregationResult = $this->getAdapter()->aggregate($query);

        $this->assertEquals(0, $aggregationResult->getDocuments()->getCount());
        $this->assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        $this->assertCount(0, $aggregationResult->getDocuments());
        $this->assertNull($aggregationResult->getDocuments()->getScrollId());
        $this->assertEquals([], $aggregationResult->getDocuments()->asArray());

        $this->assertEquals(1, count($aggregationResult->getResults()));
        $this->assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        $this->assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    /**
     * @return array
     */
    public function updateData(): array
    {
        $emptyQuery = Query::create();
        $termQuery = $query = Query::create(
            Filter::create('foo', 'bar')
        );
        $updateScript = [
            'lang' => 'painless',
            'source' => 'ctx._source.dimensions_completed=4',
        ];

        return [
            'empty query, flat update script' => [
                'query' => $emptyQuery,
                'update_script' => $updateScript,
            ],
            'empty query, properly formatted update script' => [
                'query' => $emptyQuery,
                'update_script' => [
                    'script' => $updateScript,
                ],
            ],
            'some term query, flat update script' => [
                'query' => $termQuery,
                'update_script' => $updateScript,
            ],
            'some term query, properly formatted update script' => [
                'query' => $termQuery,
                'update_script' => [
                    'script' => $updateScript,
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateData
     *
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $updateScript
     */
    public function testUpdate(QueryInterface $query, array $updateScript): void
    {
        $this->indexMock
            ->shouldNotReceive('getType');

        $responseMock = Mockery::mock(Response::class);
        $responseMock
            ->shouldReceive('getData')
            ->once()
            ->andReturn(self::UPDATE_RESPONSE_BODY);

        $this->indexMock
            ->shouldReceive('updateByQuery')
            ->once()
            ->andReturn($responseMock);

        $this->assertEquals(self::UPDATE_RESPONSE_BODY, $this->getAdapter()->update($query, $updateScript));
    }

    /**
     * @dataProvider searchResultData
     *
     * @param array $esResult
     * @param array $expectedEndResult
     */
    public function testScroll(array $esResult, array $expectedEndResult): void
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

        $responseMock = Mockery::mock(Response::class);

        $responseMock
            ->shouldReceive('getScrollId')
            ->once()
            ->andReturn(self::SCROLL_ID);

        $resultSetMock
            ->shouldReceive('getResponse')
            ->once()
            ->andReturn($responseMock);

        $this->typeMock
            ->shouldReceive('search')
            ->once()
            ->with([], ['scroll_id' => self::SCROLL_ID, 'scroll' => ElasticaAdapter::SCROLL_CONTEXT_KEEPALIVE])
            ->andReturn($resultSetMock);

        $result = $this->getAdapter()->scroll(self::SCROLL_ID);

        $this->assertEquals($expectedEndResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
    }
}
