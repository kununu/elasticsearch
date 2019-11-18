<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use Kununu\Elasticsearch\Adapter\ElasticsearchAdapter;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\ElasticaQuery;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Query\RawQuery;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticsearchAdapterTest extends MockeryTestCase
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

    /** @var \Elasticsearch\Client|\Mockery\MockInterface */
    protected $clientMock;

    protected function setUp(): void
    {
        $this->clientMock = Mockery::mock(Client::class);
    }

    protected function getAdapter(): ElasticsearchAdapter
    {
        return new ElasticsearchAdapter($this->clientMock, self::INDEX, self::TYPE);
    }

    public function testIndex(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->clientMock
            ->shouldReceive('index')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'id' => self::ID,
                    'body' => $document,
                ]
            );

        $this->getAdapter()->index(
            self::ID,
            $document
        );
    }

    public function testDelete(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            );

        $this->getAdapter()->delete(
            self::ID
        );
    }

    public function testDeleteIndex(): void
    {
        $indicesMock = Mockery::mock(IndicesNamespace::class);
        $indicesMock
            ->shouldReceive('delete')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                ]
            );

        $this->clientMock
            ->shouldReceive('indices')
            ->andReturn($indicesMock);

        $this->getAdapter()->deleteIndex(self::INDEX);
    }

    public function testDeleteIndexOtherIndex(): void
    {
        $indexToDelete = self::INDEX . '_2';

        $indicesMock = Mockery::mock(IndicesNamespace::class);
        $indicesMock
            ->shouldReceive('delete')
            ->once()
            ->with(
                [
                    'index' => $indexToDelete,
                ]
            );

        $this->clientMock
            ->shouldReceive('indices')
            ->andReturn($indicesMock);

        $this->getAdapter()->deleteIndex($indexToDelete);
    }

    /**
     * @return array
     */
    public function searchResultData(): array
    {
        return [
            'no results' => [
                'es_result' => [
                    'hits' => [
                        'total' => self::DOCUMENT_COUNT,
                        'hits' => [

                        ],
                    ],
                ],
                'adapter_result' => [],
            ],
            'one result' => [
                'es_result' => [
                    'hits' => [
                        'total' => self::DOCUMENT_COUNT,
                        'hits' => [
                            [
                                'foo' => 'bar',
                            ],
                        ],
                    ],
                ],
                'adapter_result' => [
                    ['foo' => 'bar'],
                ],
            ],
            'two results' => [
                'es_result' => [
                    'hits' => [
                        'total' => self::DOCUMENT_COUNT,
                        'hits' => [
                            [
                                'foo' => 'bar',
                            ],
                            [
                                'second' => 'result',
                                'with_more_than' => 'one field',
                            ],
                        ],
                    ],
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
     * @param array $queryVariations
     * @param array $resultsVariations
     *
     * @return array
     */
    protected function mergeQueryAndResultsVariations(array $queryVariations, array $resultsVariations): array
    {
        $allVariations = [];
        foreach ($queryVariations as $queryName => $queryVariation) {
            foreach ($resultsVariations as $resultsName => $resultsVariation) {
                $allVariations[$queryName . ', ' . $resultsName] = array_merge($queryVariation, $resultsVariation);
            }
        }

        return $allVariations;
    }

    /**
     * @return array
     */
    public function queryAndSearchResultData(): array
    {
        return $this->mergeQueryAndResultsVariations($this->queriesData(), $this->searchResultData());
    }

    /**
     * @return array
     */
    public function queryAndSearchResultVariationsData(): array
    {
        return $this->mergeQueryAndResultsVariations($this->queriesData(), $this->searchResultVariationsData());
    }

    /**
     * @return array
     */
    public function searchResultVariationsData(): array
    {
        $allVariations = [];
        foreach ($this->searchResultData() as $caseName => $case) {
            foreach ([true, false] as $scroll) {
                $newCase = $case;
                $fullCaseName = $caseName . '; scroll: ' . ($scroll ? 'true' : 'false');
                if ($scroll) {
                    $newCase['es_result']['_scroll_id'] = self::SCROLL_ID;
                }
                $newCase['scroll'] = $scroll;

                $allVariations[$fullCaseName] = $newCase;
            }
        }

        return $allVariations;
    }

    /**
     * @dataProvider queryAndSearchResultVariationsData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $esResult
     * @param array                                      $endResult
     * @param bool                                       $scroll
     */
    public function testSearchByQuery(QueryInterface $query, array $esResult, array $endResult, bool $scroll): void
    {
        $rawParams = [
            'index' => self::INDEX,
            'type' => self::TYPE,
            'body' => $query->toArray(),
        ];

        if ($scroll) {
            $rawParams['scroll'] = ElasticsearchAdapter::SCROLL_CONTEXT_KEEPALIVE;
        }

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with($rawParams)
            ->andReturn($esResult);

        $result = $this->getAdapter()->search($query, $scroll);

        $this->assertEquals($endResult, $result->asArray());
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
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     */
    public function testCount(QueryInterface $query): void
    {
        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'body' => $query->toArray(),
                ]
            )
            ->andReturn(['count' => self::DOCUMENT_COUNT]);

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getAdapter()->count($query));
    }

    /**
     * @dataProvider queryAndSearchResultData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $esResult
     * @param array                                      $endResult
     */
    public function testAggregate(QueryInterface $query, array $esResult, array $endResult): void
    {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'body' => $query->toArray(),
                ]
            )
            ->andReturn(array_merge($esResult, ['aggregations' => ['my_aggregation' => ['value' => 0.1]]]));

        $aggregationResult = $this->getAdapter()->aggregate($query);

        $this->assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        $this->assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        $this->assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        $this->assertNull($aggregationResult->getDocuments()->getScrollId());
        $this->assertEquals($esResult['hits']['hits'], $aggregationResult->getDocuments()->asArray());

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
        $emptyRawQuery = [
            'index' => self::INDEX,
            'type' => self::TYPE,
            'body' => [],
        ];
        $termQuery = Query::create(
            Filter::create('foo', 'bar')
        );
        $termRawQuery = [
            'index' => self::INDEX,
            'type' => self::TYPE,
            'body' => [
                'query' => [
                    'bool' => [
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'term' => [
                                            'foo' => 'bar',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $updateScript = [
            'lang' => 'painless',
            'source' => 'ctx._source.dimensions_completed=4',
            'params' => [],
        ];

        return [
            'empty query, flat update script' => [
                'query' => $emptyQuery,
                'raw_query' => $emptyRawQuery,
                'update_script' => $updateScript,
            ],
            'empty query, properly formatted update script' => [
                'query' => $emptyQuery,
                'raw_query' => $emptyRawQuery,
                'update_script' => [
                    'script' => $updateScript,
                ],
            ],
            'some term query, flat update script' => [
                'query' => $termQuery,
                'raw_query' => $termRawQuery,
                'update_script' => $updateScript,
            ],
            'some term query, properly formatted update script' => [
                'query' => $termQuery,
                'raw_query' => $termRawQuery,
                'update_script' => [
                    'script' => $updateScript,
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $rawQuery
     * @param array                                      $updateScript
     */
    public function testUpdate(QueryInterface $query, array $rawQuery, array $updateScript): void
    {
        $fullRawQuery = $rawQuery;
        $fullRawQuery['body']['script'] = $updateScript['script'] ?? $updateScript;

        $this->clientMock
            ->shouldReceive('updateByQuery')
            ->once()
            ->with($fullRawQuery)
            ->andReturn(self::UPDATE_RESPONSE_BODY);

        $this->assertEquals(self::UPDATE_RESPONSE_BODY, $this->getAdapter()->update($query, $updateScript));
    }

    /**
     * @dataProvider searchResultData
     *
     * @param array $esResult
     * @param array $endResult
     */
    public function testScroll(array $esResult, array $endResult): void
    {
        $esResult['_scroll_id'] = self::SCROLL_ID;

        $this->clientMock
            ->shouldReceive('scroll')
            ->once()
            ->with(['scroll_id' => self::SCROLL_ID, 'scroll' => ElasticsearchAdapter::SCROLL_CONTEXT_KEEPALIVE])
            ->andReturn($esResult);

        $result = $this->getAdapter()->scroll(self::SCROLL_ID);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
    }
}
