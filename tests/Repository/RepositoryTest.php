<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Kununu\Elasticsearch\Exception\BulkException;
use Kununu\Elasticsearch\Exception\DeleteException;
use Kununu\Elasticsearch\Exception\DocumentNotFoundException;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Exception\UpsertException;
use Kununu\Elasticsearch\Exception\WriteOperationException;
use Kununu\Elasticsearch\Query\Aggregation;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Query\RawQuery;
use Kununu\Elasticsearch\Repository\EntityFactoryInterface;
use Kununu\Elasticsearch\Repository\EntitySerializerInterface;
use Kununu\Elasticsearch\Repository\PersistableEntityInterface;
use Kununu\Elasticsearch\Repository\Repository;
use Kununu\Elasticsearch\Repository\RepositoryConfiguration;
use Kununu\Elasticsearch\Repository\RepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * @group unit
 */
class RepositoryTest extends MockeryTestCase
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
     * @param array $additionalConfig
     *
     * @return \Kununu\Elasticsearch\Repository\RepositoryInterface
     */
    private function getRepository(array $additionalConfig = []): RepositoryInterface
    {
        $repo = new Repository(
            $this->clientMock,
            array_merge(
                [
                    'index_read' => self::INDEX['read'],
                    'index_write' => self::INDEX['write'],
                    'type' => self::TYPE,
                ],
                $additionalConfig
            )
        );

        $repo->setLogger($this->loggerMock);

        return $repo;
    }

    public function testSaveArray(): void
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

    public function testSaveWithForcedRefresh(): void
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
                    'refresh' => true,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['force_refresh_on_write' => true])->save(
            self::ID,
            $document
        );
    }

    public function testSaveObjectWithEntitySerializer(): void
    {
        $mySerializer = new class implements EntitySerializerInterface {
            public function toElastic($entity): array
            {
                return (array)$entity;
            }
        };

        $document = new stdClass();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->shouldReceive('index')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                    'body' => [
                        'property_a' => 'a',
                        'property_b' => 'b',
                    ],
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['entity_serializer' => $mySerializer])->save(
            self::ID,
            $document
        );
    }

    public function testSaveObjectWithEntityClass(): void
    {
        $document = $this->getEntityClass();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->shouldReceive('index')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                    'body' => [
                        'property_a' => 'a',
                        'property_b' => 'b',
                    ],
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['entity_class' => get_class($this->getEntityClass())])->save(
            self::ID,
            $document
        );
    }

    public function testSaveObjectFailsWithoutEntitySerializerAndEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No entity serializer configured while trying to persist object');

        $this->getRepository()->save(
            self::ID,
            new stdClass()
        );
    }

    /**
     * @return array
     */
    public function invalidDataTypesForSave(): array
    {
        return [
            [7],
            [7.7],
            [''],
            ['string'],
            [true],
            [false],
            [null],
        ];
    }

    /**
     * @dataProvider invalidDataTypesForSave
     *
     * @param mixed $entity
     */
    public function testSaveFailsWithInvalidDataType($entity): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be of type array or object');

        $this->getRepository()->save(
            self::ID,
            $entity
        );
    }

    public function testSaveArrayFails(): void
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

        try {
            $this->getRepository()->save(
                self::ID,
                $document
            );
        } catch (UpsertException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals(self::ID, $e->getDocumentId());
            $this->assertEquals($document, $e->getDocument());
        }
    }

    public function testSaveBulkWithArrays(): void
    {
        $documents = [
            'document_id_1' => ['whatever' => 'just some data'],
            'document_id_2' => ['whatever' => 'just some more data'],
            'document_id_3' => ['whatever' => 'even more data'],
            'document_id_4' => ['whatever' => 'what is this even'],
        ];

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'body' => [
                        ['index' => ['_id' => 'document_id_1']],
                        $documents['document_id_1'],
                        ['index' => ['_id' => 'document_id_2']],
                        $documents['document_id_2'],
                        ['index' => ['_id' => 'document_id_3']],
                        $documents['document_id_3'],
                        ['index' => ['_id' => 'document_id_4']],
                        $documents['document_id_4'],
                    ],
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository()->saveBulk($documents);
    }

    public function testSaveBulkWithForcedRefresh(): void
    {
        $documents = [
            'document_id_1' => ['whatever' => 'just some data'],
            'document_id_2' => ['whatever' => 'just some more data'],
            'document_id_3' => ['whatever' => 'even more data'],
            'document_id_4' => ['whatever' => 'what is this even'],
        ];

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'body' => [
                        ['index' => ['_id' => 'document_id_1']],
                        $documents['document_id_1'],
                        ['index' => ['_id' => 'document_id_2']],
                        $documents['document_id_2'],
                        ['index' => ['_id' => 'document_id_3']],
                        $documents['document_id_3'],
                        ['index' => ['_id' => 'document_id_4']],
                        $documents['document_id_4'],
                    ],
                    'refresh' => true,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['force_refresh_on_write' => true])->saveBulk($documents);
    }

    public function testSaveBulkObjectsWithEntitySerializer(): void
    {
        $mySerializer = new class implements EntitySerializerInterface {
            public function toElastic($entity): array
            {
                return (array)$entity;
            }
        };

        $documents = [];
        for ($ii = 0; $ii < 3; $ii++) {
            $document = new stdClass();
            $document->property_a = 'a' . $ii;
            $document->property_b = 'b' . $ii;
            $documents['doc_' . $ii] = $document;
        }

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'body' => [
                        ['index' => ['_id' => 'doc_0']],
                        ['property_a' => 'a0', 'property_b' => 'b0'],
                        ['index' => ['_id' => 'doc_1']],
                        ['property_a' => 'a1', 'property_b' => 'b1'],
                        ['index' => ['_id' => 'doc_2']],
                        ['property_a' => 'a2', 'property_b' => 'b2'],
                    ],

                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['entity_serializer' => $mySerializer])->saveBulk($documents);
    }

    public function testSaveBulkObjectsWithEntityClass(): void
    {
        $documents = [];
        for ($ii = 0; $ii < 3; $ii++) {
            $document = $this->getEntityClass();
            $document->property_a = 'a' . $ii;
            $document->property_b = 'b' . $ii;
            $documents['doc_' . $ii] = $document;
        }

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'body' => [
                        ['index' => ['_id' => 'doc_0']],
                        ['property_a' => 'a0', 'property_b' => 'b0'],
                        ['index' => ['_id' => 'doc_1']],
                        ['property_a' => 'a1', 'property_b' => 'b1'],
                        ['index' => ['_id' => 'doc_2']],
                        ['property_a' => 'a2', 'property_b' => 'b2'],
                    ],
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['entity_class' => get_class($this->getEntityClass())])->saveBulk($documents);
    }

    public function testSaveBulkObjectsFailsWithoutEntitySerializerAndEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No entity serializer configured while trying to persist object');

        $this->getRepository()->saveBulk([self::ID => new stdClass()]);
    }

    /**
     * @dataProvider invalidDataTypesForSave
     *
     * @param mixed $entity
     */
    public function testSaveBulkFailsWithInvalidDataType($entity): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be of type array or object');

        $this->getRepository()->saveBulk([self::ID => $entity]);
    }

    public function testSaveBulkArrayFails(): void
    {
        $documents = [
            self::ID => [
                'foo' => 'bar',
            ],
        ];

        $expectedOperations = [
            ['index' => ['_id' => self::ID]],
            ['foo' => 'bar'],
        ];

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'body' => $expectedOperations,
                ]
            )
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->saveBulk($documents);
        } catch (BulkException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals($expectedOperations, $e->getOperations());
        }
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

    public function testDeleteWithForcedRefresh(): void
    {
        $this->clientMock
            ->shouldReceive('delete')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                    'refresh' => true,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['force_refresh_on_write' => true])->delete(
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

        try {
            $this->getRepository()->delete(
                self::ID
            );
        } catch (DeleteException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals(self::ID, $e->getDocumentId());
        }
    }

    public function testDeleteFailsBecauseDocumentNotFound(): void
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
            ->andThrow(new Missing404Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldNotReceive('error');

        try {
            $this->getRepository()->delete(
                self::ID
            );
        } catch (DocumentNotFoundException $e) {
            $this->assertEquals(self::ERROR_PREFIX . 'No document found with id ' . self::ID, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals(self::ID, $e->getDocumentId());
        }
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
                        'total' => [
                            'value' => self::DOCUMENT_COUNT
                        ],
                        'hits' => [

                        ],
                    ],
                ],
                'end_result' => [],
            ],
            'one result' => [
                'es_result' => [
                    'hits' => [
                        'total' => [
                            'value' => self::DOCUMENT_COUNT
                        ],
                        'hits' => [
                            [
                                '_index' => self::INDEX['read'],
                                '_score' => 77,
                                '_source' => [
                                    'foo' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
                'end_result' => [
                    [
                        '_index' => self::INDEX['read'],
                        '_score' => 77,
                        '_source' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
            'two results' => [
                'es_result' => [
                    'hits' => [
                        'total' => [
                            'value' => self::DOCUMENT_COUNT
                        ],
                        'hits' => [
                            [
                                '_index' => self::INDEX['read'],
                                '_score' => 77,
                                '_source' => [
                                    'foo' => 'bar',
                                ],
                            ],
                            [
                                '_index' => self::INDEX['read'],
                                '_score' => 77,
                                '_source' => [
                                    'second' => 'result',
                                    'with_more_than' => 'one field',
                                ],
                            ],
                        ],
                    ],
                ],
                'end_result' => [
                    [
                        '_index' => self::INDEX['read'],
                        '_score' => 77,
                        '_source' => [
                            'foo' => 'bar',
                        ],
                    ],
                    [
                        '_index' => self::INDEX['read'],
                        '_score' => 77,
                        '_source' => [
                            'second' => 'result',
                            'with_more_than' => 'one field',
                        ],
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

        try {
            $this->getRepository()->findByQuery(Query::create());
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
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

        try {
            $this->getRepository()->findByScrollId($scrollId);
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
    }

    /**
     * @return array
     */
    public function findByIdResultData(): array
    {
        return [
            'no result' => [
                'es_result' => [
                    'found' => false,
                ],
                'end_result' => null,
            ],
            'document found' => [
                'es_result' => [
                    '_index' => self::INDEX['read'],
                    '_type' => self::TYPE,
                    '_version' => 1,
                    'found' => true,
                    '_source' => [
                        'foo' => 'bar',
                    ],
                ],
                'end_result' => [
                    '_index' => self::INDEX['read'],
                    '_type' => self::TYPE,
                    '_version' => 1,
                    'found' => true,
                    '_source' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider findByIdResultData
     *
     * @param array      $esResult
     * @param array|null $endResult
     */
    public function testFindById(array $esResult, $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            )
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($endResult, $this->getRepository()->findById(self::ID));
    }

    /**
     * @dataProvider findByIdResultData
     *
     * @param array      $esResult
     * @param array|null $endResult
     */
    public function testFindByIdWithSourceField(array $esResult, $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                    '_source' => ['foo', 'foo2']
                ]
            )
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($endResult, $this->getRepository()->findById(self::ID, ['foo', 'foo2']));
    }

    /**
     * @dataProvider findByIdResultData
     *
     * @param array      $esResult
     * @param array|null $endResult
     */
    public function testFindByIdWithEmptySourceField(array $esResult, $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            )
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($endResult, $this->getRepository()->findById(self::ID, []));
    }

    public function findByIdFails(): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            )
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->findById(self::ID);
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
    }

    public function findByIdFailsWith404(): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            )
            ->andThrow(new Missing404Exception());

        $this->assertNull($this->getRepository()->findById(self::ID));
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

        try {
            $this->getRepository()->countByQuery(Query::create());
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
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

        try {
            $this->getRepository()->count();
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
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

        try {
            $this->getRepository()->aggregateByQuery($query);
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
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

    public function testUpdateByQueryWithForcedRefresh(): void
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
                    'refresh' => true,
                ]
            )
            ->andReturn($responseBody);

        $this->loggerMock
            ->shouldNotReceive('error');

        $repository = $this->getRepository(['force_refresh_on_write' => true]);

        $this->assertEquals(
            $responseBody,
            $repository->updateByQuery($query, $updateScript)
        );
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

        try {
            $this->getRepository()->updateByQuery(Query::create(), ['script' => []]);
        } catch (WriteOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
        }
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
        ], $this) extends Repository
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

    public function testPostSaveBulkIsCalled(): void
    {
        $documents = [
            self::ID => [
                'whatever' => 'just some data',
            ],
        ];

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'type' => self::TYPE,
                    'body' => [
                        ['index' => ['_id' => self::ID]],
                        ['whatever' => 'just some data'],
                    ],
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $manager = new class($this->clientMock, [
            'index_write' => self::INDEX['write'],
            'type' => self::TYPE,
        ], $this) extends Repository {
            /**
             * @var \Mockery\Adapter\Phpunit\MockeryTestCase
             */
            protected $test;

            public function __construct(Client $client, array $config, MockeryTestCase $test)
            {
                parent::__construct($client, $config);
                $this->test = $test;
            }

            protected function postSaveBulk(array $entities): void
            {
                $this->test->assertEquals(
                    [
                        $this->test::ID => [
                            'whatever' => 'just some data',
                        ],
                    ],
                    $entities
                );
            }
        };

        $manager->saveBulk($documents);
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
        ], $this) extends Repository
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

    /**
     * @param array $baseData
     *
     * @return array
     */
    protected function modifySearchResultDataForEntityUsecases(array $baseData): array
    {
        return array_map(
            function (array $variables) {
                $variables['end_result'] = array_map(
                    function (array $result) {
                        $entity = new stdClass();
                        foreach ($result['_source'] as $key => $value) {
                            $entity->$key = $value;
                        }
                        $entity->_meta = ['_index' => $result['_index'], '_score' => $result['_score']];

                        return $entity;
                    },
                    $variables['es_result']['hits']['hits'] ?? []
                );

                return $variables;
            },
            $baseData
        );
    }

    /**
     * @return array
     */
    public function queryAndSearchResultVariationsWithEntitiesData(): array
    {
        return $this->modifySearchResultDataForEntityUsecases($this->queryAndSearchResultVariationsData());
    }

    /**
     * @return array
     */
    public function queryAndSearchResultWithEntitiesData(): array
    {
        return $this->modifySearchResultDataForEntityUsecases($this->queryAndSearchResultData());
    }

    /**
     * @return array
     */
    public function searchResultWithEntitiesData(): array
    {
        return $this->modifySearchResultDataForEntityUsecases($this->searchResultData());
    }

    /**
     * @return \Kununu\Elasticsearch\Repository\EntityFactoryInterface
     */
    protected function getEntityFactory(): EntityFactoryInterface
    {
        return new class implements EntityFactoryInterface
        {
            public function fromDocument(array $document, array $metaData)
            {
                $entity = new stdClass();
                foreach ($document as $key => $value) {
                    $entity->$key = $value;
                }
                $entity->_meta = $metaData;

                return $entity;
            }
        };
    }

    /**
     * @dataProvider queryAndSearchResultVariationsWithEntitiesData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $esResult
     * @param array                                      $endResult
     * @param bool                                       $scroll
     */
    public function testFindByQueryWithEntityFactory(
        QueryInterface $query,
        array $esResult,
        array $endResult,
        bool $scroll
    ): void {
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

        $this->loggerMock
            ->shouldNotReceive('error');

        $repository = $this->getRepository(['entity_factory' => $this->getEntityFactory()]);

        $result = $scroll
            ? $repository->findScrollableByQuery($query)
            : $repository->findByQuery($query);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            $this->assertNull($result->getScrollId());
        }

        if (!empty($result)) {
            foreach ($result as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    /**
     * @dataProvider searchResultWithEntitiesData
     *
     * @param array $esResult
     * @param array $endResult
     */
    public function testFindByScrollIdWithEntityFactory(array $esResult, array $endResult): void
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
            ->andReturn(array_merge($esResult, ['_scroll_id' => $scrollId]));

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository(['entity_factory' => $this->getEntityFactory()])->findByScrollId($scrollId);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        $this->assertEquals($scrollId, $result->getScrollId());

        if (!empty($result)) {
            foreach ($result as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    /**
     * @dataProvider queryAndSearchResultWithEntitiesData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $esResult
     * @param array                                      $endResult
     */
    public function testAggregateByQueryWithEntityFactory(
        QueryInterface $query,
        array $esResult,
        array $endResult
    ): void {
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

        $aggregationResult = $this
            ->getRepository(['entity_factory' => $this->getEntityFactory()])
            ->aggregateByQuery($query);

        $this->assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        $this->assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        $this->assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        $this->assertNull($aggregationResult->getDocuments()->getScrollId());
        $this->assertEquals($endResult, $aggregationResult->getDocuments()->asArray());

        if (!empty($aggregationResult->getDocuments())) {
            foreach ($aggregationResult->getDocuments() as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }

        $this->assertEquals(1, count($aggregationResult->getResults()));
        $this->assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        $this->assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    /**
     * @return \Kununu\Elasticsearch\Repository\PersistableEntityInterface
     */
    protected function getEntityClass(): PersistableEntityInterface
    {
        return new class extends stdClass implements PersistableEntityInterface
        {
            /**
             * @return array
             */
            public function toElastic(): array
            {
                return (array)$this;
            }

            /**
             * @param array $document the raw document as found in the _source field of the raw Elasticsearch response
             * @param array $metaData contains all "underscore-fields" delivered in the raw Elasticsearch response (e.g. _score)
             *
             * @return mixed
             */
            public static function fromElasticDocument(array $document, array $metaData)
            {
                $entity = new stdClass();
                foreach ($document as $key => $value) {
                    $entity->$key = $value;
                }
                $entity->_meta = $metaData;

                return $entity;
            }
        };
    }

    /**
     * @dataProvider queryAndSearchResultVariationsWithEntitiesData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $esResult
     * @param array                                      $endResult
     * @param bool                                       $scroll
     */
    public function testFindByQueryWithEntityClass(
        QueryInterface $query,
        array $esResult,
        array $endResult,
        bool $scroll
    ): void {
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

        $this->loggerMock
            ->shouldNotReceive('error');

        $repository = $this->getRepository(['entity_class' => get_class($this->getEntityClass())]);

        $result = $scroll
            ? $repository->findScrollableByQuery($query)
            : $repository->findByQuery($query);

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        if ($scroll) {
            $this->assertEquals(self::SCROLL_ID, $result->getScrollId());
        } else {
            $this->assertNull($result->getScrollId());
        }

        if (!empty($result)) {
            foreach ($result as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    /**
     * @dataProvider searchResultWithEntitiesData
     *
     * @param array $esResult
     * @param array $endResult
     */
    public function testFindByScrollIdWithEntityClass(array $esResult, array $endResult): void
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
            ->andReturn(array_merge($esResult, ['_scroll_id' => $scrollId]));

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository(['entity_class' => get_class($this->getEntityClass())])->findByScrollId(
            $scrollId
        );

        $this->assertEquals($endResult, $result->asArray());
        $this->assertEquals(self::DOCUMENT_COUNT, $result->getTotal());
        $this->assertEquals($scrollId, $result->getScrollId());

        if (!empty($result)) {
            foreach ($result as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }
    }

    /**
     * @dataProvider queryAndSearchResultWithEntitiesData
     *
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $esResult
     * @param array                                      $endResult
     */
    public function testAggregateByQueryWithEntityClass(
        QueryInterface $query,
        array $esResult,
        array $endResult
    ): void {
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

        $aggregationResult = $this
            ->getRepository(['entity_class' => get_class($this->getEntityClass())])
            ->aggregateByQuery($query);

        $this->assertEquals(count($esResult['hits']['hits']), $aggregationResult->getDocuments()->getCount());
        $this->assertEquals(self::DOCUMENT_COUNT, $aggregationResult->getDocuments()->getTotal());
        $this->assertCount(count($esResult['hits']['hits']), $aggregationResult->getDocuments());
        $this->assertNull($aggregationResult->getDocuments()->getScrollId());
        $this->assertEquals($endResult, $aggregationResult->getDocuments()->asArray());

        if (!empty($aggregationResult->getDocuments())) {
            foreach ($aggregationResult->getDocuments() as $entity) {
                $this->assertEquals(['_index' => self::INDEX['read'], '_score' => 77], $entity->_meta);
            }
        }

        $this->assertEquals(1, count($aggregationResult->getResults()));
        $this->assertEquals('my_aggregation', $aggregationResult->getResultByName('my_aggregation')->getName());
        $this->assertEquals(0.1, $aggregationResult->getResultByName('my_aggregation')->getValue());
    }

    /**
     * @return array
     */
    public function findByIdResultWithEntitiesData(): array
    {
        return array_map(
            function (array $variables) {
                if ($variables['es_result']['found']) {
                    $entity = new stdClass();
                    foreach ($variables['es_result']['_source'] as $key => $value) {
                        $entity->$key = $value;
                    }
                    $entity->_meta = [
                        '_index' => $variables['es_result']['_index'],
                        '_type' => $variables['es_result']['_type'],
                        '_version' => $variables['es_result']['_version'],
                        'found' => $variables['es_result']['found'],
                    ];

                    $variables['end_result'] = $entity;
                }

                return $variables;
            },
            $this->findByIdResultData()
        );
    }

    /**
     * @dataProvider findByIdResultWithEntitiesData
     *
     * @param array      $esResult
     * @param array|null $endResult
     */
    public function testFindByIdWithEntityClass(array $esResult, $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            )
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository(['entity_class' => get_class($this->getEntityClass())])->findById(
            self::ID
        );

        $this->assertEquals($endResult, $result);
        if ($endResult) {
            $this->assertEquals(
                ['_index' => self::INDEX['read'], '_type' => self::TYPE, '_version' => 1, 'found' => true],
                $result->_meta
            );
        }
    }

    /**
     * @dataProvider findByIdResultWithEntitiesData
     *
     * @param array      $esResult
     * @param array|null $endResult
     */
    public function testFindByIdWithEntityFactory(array $esResult, $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['read'],
                    'type' => self::TYPE,
                    'id' => self::ID,
                ]
            )
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this->getRepository(['entity_factory' => $this->getEntityFactory()])->findById(
            self::ID
        );

        $this->assertEquals($endResult, $result);
        if ($endResult) {
            $this->assertEquals(
                ['_index' => self::INDEX['read'], '_type' => self::TYPE, '_version' => 1, 'found' => true],
                $result->_meta
            );
        }
    }
}
