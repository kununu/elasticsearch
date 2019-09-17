<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Manager;

use App\Services\Elasticsearch\Adapter\ElasticsearchAdapter;
use App\Services\Elasticsearch\Query\Query;
use App\Services\Elasticsearch\Query\QueryInterface;
use App\Tests\Unit\Services\Elasticsearch\ElasticsearchManagerTestTrait;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticsearchAdapterTest extends MockeryTestCase
{
    use ElasticsearchManagerTestTrait;

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

        $this->getAdapter()->deleteIndex();
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
     * @dataProvider searchResultData
     *
     * @param array $esResult
     * @param array $endResult
     */
    public function testSearchWithEmptyQuery(array $esResult, array $endResult): void
    {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'body' => [
                        'query' => [
                            'match_all' => new \stdClass(),
                        ],
                    ],
                ]
            )
            ->andReturn($esResult);

        $result = $this->getAdapter()->search(Query::create());

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
    }

    /**
     * @dataProvider searchResultData
     *
     * @param array $esResult
     * @param array $endResult
     */
    public function testSearchByQuery(array $esResult, array $endResult): void
    {
        $query = Query::create(
            (new BoolQuery())
                ->addMust((new Term())->setTerm('foo', 'bar'))
        );

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'term' => [
                                            'foo' => [
                                                'value' => 'bar',
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->andReturn($esResult);

        $result = $this->getAdapter()->search($query);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
    }

    public function testCountWithEmptyQuery(): void
    {
        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->andReturn(['count' => self::DOCUMENT_COUNT]);

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getAdapter()->count(Query::create()));
    }

    public function testCountByQuery(): void
    {
        $query = Query::create(
            (new BoolQuery())
                ->addMust((new Term())->setTerm('foo', 'bar'))
        );

        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'term' => [
                                            'foo' => [
                                                'value' => 'bar',
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->andReturn(['count' => self::DOCUMENT_COUNT]);

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getAdapter()->count($query));
    }

    public function testAggregateWithEmptyQuery(): void
    {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'body' => [
                        'query' => [
                            'match_all' => new \stdClass(),
                        ],
                    ],
                ]
            )
            ->andReturn(['aggregations' => ['foo' => 'bar']]);

        $this->assertEquals(['foo' => 'bar'], $this->getAdapter()->aggregate(Query::create()));
    }

    public function testAggregateByQuery(): void
    {
        $query = Query::create(
            (new BoolQuery())
                ->addMust((new Term())->setTerm('foo', 'bar'))
        );

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'type' => self::TYPE,
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'term' => [
                                            'foo' => [
                                                'value' => 'bar',
                                                'boost' => 1.0,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            )
            ->andReturn(['aggregations' => ['foo' => 'bar']]);

        $this->assertEquals(['foo' => 'bar'], $this->getAdapter()->aggregate($query));
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
            'body' => $emptyQuery->toArray(),
        ];
        $termQuery = Query::create(
            (new BoolQuery())
                ->addMust((new Term())->setTerm('foo', 'bar'))
        );
        $termRawQuery = [
            'index' => self::INDEX,
            'type' => self::TYPE,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'foo' => [
                                        'value' => 'bar',
                                        'boost' => 1.0,
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
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $rawQuery
     * @param array                                            $updateScript
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
}
