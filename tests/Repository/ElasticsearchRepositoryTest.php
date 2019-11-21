<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Client;
use Kununu\Elasticsearch\Exception\RepositoryException;
use Kununu\Elasticsearch\Query\Aggregation;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Query\RawQuery;
use Kununu\Elasticsearch\Repository\ElasticsearchRepository;
use Kununu\Elasticsearch\Repository\ElasticsearchRepositoryInterface;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;

/**
 * @group unit
 */
class ElasticsearchRepositoryTest extends MockeryTestCase
{
    protected const INDEX = [
        'read' => 'some_index_read',
        'write' => 'some_index_write',
    ];
    protected const TYPE = '_doc';
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';
    public const ID = 'can_be_anything';
    protected const DOCUMENT_COUNT = 42;
    protected const SCROLL_ID = 'DnF1ZXJ5VGhlbkZldGNoBQAAAAAAAAFbFkJVNEdjZWVjU';

    /** @var \Elasticsearch\Client|\Mockery\MockInterface */
    protected $clientMock;

    /** @var \Psr\Log\LoggerInterface|\Mockery\MockInterface */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->clientMock = Mockery::mock(Client::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
    }

    /**
     * @return \Kununu\Elasticsearch\Repository\ElasticsearchRepositoryInterface
     */
    private function getRepository(): ElasticsearchRepositoryInterface
    {
        $repo = new ElasticsearchRepository(
            $this->clientMock,
            [
                'index_read' => self::INDEX['read'],
                'index_write' => self::INDEX['write'],
                'type' => self::TYPE,
            ]
        );

        $repo->setLogger($this->loggerMock);

        return $repo;
    }

    public function testSave(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->clientMock
            ->shouldReceive('index')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                    'body' => $document,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository()->save(
            self::ID,
            $document
        );
    }

    public function testSaveFails(): void
    {
        $document = [
            'foo' => 'bar',
        ];

        $this->clientMock
            ->shouldReceive('index')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                    'body' => $document,
                ]
            )
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(RepositoryException::class);
        $this->getRepository()->save(
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
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository()->delete(
            self::ID
        );
    }

    public function testDeleteFails(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            )
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(RepositoryException::class);
        $this->getRepository()->delete(
            self::ID
        );
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
    public function testFindByQuery(QueryInterface $query, array $esResult, array $endResult, bool $scroll): void
    {
        $rawParams = [
            'index' => self::INDEX['read'],
            'type' => self::TYPE,
            'body' => $query->toArray(),
        ];

        if ($scroll) {
            $rawParams['scroll'] = RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
        }

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with($rawParams)
            ->andReturn($esResult);

        $result = $scroll
            ? $this->getRepository()->findScrollableByQuery($query)
            : $this->getRepository()->findByQuery($query);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            $this->assertNull($result->getScrollId());
        }
    }

    public function testFindByQueryFails(): void
    {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(RepositoryException::class);

        $this->getRepository()->findByQuery(Query::create());
    }

    /**
     * @dataProvider searchResultData
     *
     * @param array $esResult
     * @param array $endResult
     */
    public function testFindByScrollId(array $esResult, array $endResult): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->shouldReceive('scroll')
            ->once()
            ->with(
                [
                    'scroll_id' => $scrollId,
                    'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
                ]
            )
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository()->findByScrollId($scrollId);

        $this->assertEquals($endResult, $result->asArray());
    }

    public function testFindByScrollIdFails(): void
    {
        $scrollId = 'foobar';

        $this->clientMock
            ->shouldReceive('scroll')
            ->once()
            ->with(
                [
                    'scroll_id' => $scrollId,
                    'scroll' => RepositoryConfiguration::DEFAULT_SCROLL_CONTEXT_KEEPALIVE,
                ]
            )
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(RepositoryException::class);

        $this->getRepository()->findByScrollId($scrollId);
    }

    /**
     * @dataProvider queriesData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     */
    public function testCountByQuery(QueryInterface $query): void
    {
        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'body' => $query->toArray(),
                ]
            )
            ->andReturn(['count' => self::DOCUMENT_COUNT]);

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getRepository()->countByQuery($query));
    }

    public function testCountByQueryFails(): void
    {
        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(RepositoryException::class);

        $this->getRepository()->countByQuery(Query::create());
    }

    public function testCount(): void
    {
        $query = Query::create();

        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'body' => $query->toArray(),
                ]
            )
            ->andReturn(['count' => self::DOCUMENT_COUNT]);

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getRepository()->count());
    }

    public function testCountFails(): void
    {
        $this->clientMock
            ->shouldReceive('count')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(RepositoryException::class);

        $this->getRepository()->count();
    }

    /**
     * @dataProvider queryAndSearchResultData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $esResult
     */
    public function testAggregateByQuery(QueryInterface $query, array $esResult): void
    {
        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'body' => $query->toArray(),
                ]
            )
            ->andReturn(array_merge($esResult, ['aggregations' => ['my_aggregation' => ['value' => 0.1]]]));

        $aggregationResult = $this->getRepository()->aggregateByQuery($query);

        $this->assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        $this->assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        $this->assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        $this->assertNull($aggregationResult->getDocuments()->getScrollId());
        $this->assertEquals($esResult['hits']['hits'], $aggregationResult->getDocuments()->asArray());

        $this->assertEquals(1, count($aggregationResult->getResults()));
        $this->assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        $this->assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    public function testAggregateByQueryFails(): void
    {
        $query = Query::create(
            Filter::create('foo', 'bar'),
            Aggregation::create('foo', Aggregation\Metric::EXTENDED_STATS)
        );

        $this->clientMock
            ->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(RepositoryException::class);

        $this->getRepository()->aggregateByQuery($query);
    }

    public function testUpdateByQuery(): void
    {
        $query = Query::create(
            Filter::create('foo', 'bar')
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

        $this->clientMock
            ->shouldReceive('updateByQuery')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'body' => array_merge(
                        $query->toArray(),
                        [
                            'script' => [
                                'lang' => 'painless',
                                'source' => 'ctx._source.dimensions_completed=4',
                                'params' => [],
                            ],
                        ]
                    ),
                ]
            )
            ->andReturn($responseBody);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($responseBody, $this->getRepository()->updateByQuery($query, $updateScript));
    }

    public function testUpdateByQueryFails(): void
    {
        $this->clientMock
            ->shouldReceive('updateByQuery')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(RepositoryException::class);

        $this->getRepository()->updateByQuery(Query::create(), ['script' => []]);
    }

    public function testPostSaveIsCalled(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->clientMock
            ->shouldReceive('index')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                    'body' => $document,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $manager = new class($this->clientMock, [
            'index_write' => self::INDEX['write'],
            'type' => self::TYPE,
        ], $this) extends ElasticsearchRepository
        {
            /**
             * @var \Mockery\Adapter\Phpunit\MockeryTestCase
             */
            protected $test;

            public function __construct(Client $client, array $config, MockeryTestCase $test)
            {
                parent::__construct($client, $config);
                $this->test = $test;
            }

            protected function postSave(string $id, array $document): void
            {
                $this->test->assertEquals($this->test::ID, $id);
                $this->test->assertEquals(
                    [
                        'whatever' => 'just some data',
                    ],
                    $document
                );
            }
        };

        $manager->save(
            self::ID,
            $document
        );
    }

    public function testPostDeleteIsCalled(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $manager = new class($this->clientMock, [
            'index_write' => self::INDEX['write'],
            'type' => self::TYPE,
        ], $this) extends ElasticsearchRepository
        {
            /**
             * @var \Mockery\Adapter\Phpunit\MockeryTestCase
             */
            protected $test;

            public function __construct(Client $client, array $config, MockeryTestCase $test)
            {
                parent::__construct($client, $config);
                $this->test = $test;
            }

            protected function postDelete(string $id): void
            {
                $this->test->assertEquals($this->test::ID, $id);
            }
        };

        $manager->delete(self::ID);
    }
}
