<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Elasticsearch\Client as ElasticClient;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\RawQuery;
use Kununu\Elasticsearch\Repository\RepositoryInterface;
use Kununu\Elasticsearch\Tests\AbstractClientTestCase;
use Kununu\Elasticsearch\Tests\Stub\EntityFactoryStub;
use Kununu\Elasticsearch\Tests\Stub\EntitySerializerStub;
use Kununu\Elasticsearch\Tests\Stub\PersistableEntityStub;
use OpenSearch\Client as OpenSearchClient;
use Psr\Log\LoggerAwareInterface;

abstract class AbstractRepositoryTestCase extends AbstractClientTestCase
{
    public const string ID = 'can_be_anything';
    public const string ID_2 = 'can_also_be_anything';

    protected const array INDEX = [
        'read'  => 'some_index_read',
        'write' => 'some_index_write',
    ];
    protected const string ERROR_MESSAGE = 'Any error';
    protected const int DOCUMENT_COUNT = 42;
    protected const string SCROLL_ID = 'DnF1ZXJ5VGhlbkZldGNoBQAAAAAAAAFbFkJVNEdjZWVjU';

    public static function invalidDataTypesForSaveAndUpsertDataProvider(): array
    {
        return [
            'integer'      => [7],
            'float'        => [7.7],
            'empty_string' => [''],
            'string'       => ['string'],
            'true'         => [true],
            'false'        => [false],
            'null'         => [null],
        ];
    }

    public static function queryAndSearchResultVariationsDataProvider(): array
    {
        return self::mergeQueryAndResultsVariations(
            self::queriesDataProvider(),
            self::searchResultVariationsDataProvider()
        );
    }

    public static function queryAndSearchResultDataProvider(): array
    {
        return self::removeEndResults(
            self::mergeQueryAndResultsVariations(
                self::queriesDataProvider(),
                self::searchResultDataProvider()
            )
        );
    }

    public static function queryAndSearchResultVariationsWithEntitiesDataProvider(): array
    {
        return self::modifySearchResultDataForEntityUseCases(self::queryAndSearchResultVariationsDataProvider());
    }

    public static function queryAndSearchResultWithEntitiesDataProvider(): array
    {
        return self::modifySearchResultDataForEntityUseCases(self::queryAndSearchResultDataProvider());
    }

    public static function searchResultWithEntitiesDataProvider(): array
    {
        return self::modifySearchResultDataForEntityUseCases(self::searchResultDataProvider());
    }

    public static function queriesDataProvider(): array
    {
        return [
            'empty_kununu_query'     => [
                'query' => Query::create(),
            ],
            'some_kununu_term_query' => [
                'query' => Query::create(
                    Filter::create('foo', 'bar')
                ),
            ],
            'empty_raw_query'        => [
                'query' => RawQuery::create(),
            ],
            'some_raw_term_query'    => [
                'query' => RawQuery::create(['query' => ['bool' => ['must' => [['term' => ['foo' => 'bar']]]]]]),
            ],
        ];
    }

    public static function searchResultDataProvider(): array
    {
        return [
            'no_results'  => [
                'result'    => [
                    'hits' => [
                        'total' => [
                            'value' => self::DOCUMENT_COUNT,
                        ],
                        'hits'  => [
                        ],
                    ],
                ],
                'endResult' => [],
            ],
            'one_result'  => [
                'result'    => [
                    'hits' => [
                        'total' => [
                            'value' => self::DOCUMENT_COUNT,
                        ],
                        'hits'  => [
                            [
                                '_index'  => self::INDEX['read'],
                                '_score'  => 77,
                                '_source' => [
                                    'foo' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
                'endResult' => [
                    [
                        '_index'  => self::INDEX['read'],
                        '_score'  => 77,
                        '_source' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
            'two_results' => [
                'result'    => [
                    'hits' => [
                        'total' => [
                            'value' => self::DOCUMENT_COUNT,
                        ],
                        'hits'  => [
                            [
                                '_index'  => self::INDEX['read'],
                                '_score'  => 77,
                                '_source' => [
                                    'foo' => 'bar',
                                ],
                            ],
                            [
                                '_index'  => self::INDEX['read'],
                                '_score'  => 77,
                                '_source' => [
                                    'second'       => 'result',
                                    'withMoreThan' => 'one field',
                                ],
                            ],
                        ],
                    ],
                ],
                'endResult' => [
                    [
                        '_index'  => self::INDEX['read'],
                        '_score'  => 77,
                        '_source' => [
                            'foo' => 'bar',
                        ],
                    ],
                    [
                        '_index'  => self::INDEX['read'],
                        '_score'  => 77,
                        '_source' => [
                            'second'       => 'result',
                            'withMoreThan' => 'one field',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function searchResultVariationsDataProvider(): array
    {
        $allVariations = [];
        foreach (self::searchResultDataProvider() as $caseName => $case) {
            foreach ([true, false] as $scroll) {
                $newCase = $case;
                $fullCaseName = sprintf('%s_with_scroll_%s', $caseName, json_encode($scroll));
                if ($scroll) {
                    $newCase['result']['_scroll_id'] = self::SCROLL_ID;
                }
                $newCase['scroll'] = $scroll;

                $allVariations[$fullCaseName] = $newCase;
            }
        }

        return $allVariations;
    }

    abstract protected function createRepository(
        ElasticClient|OpenSearchClient $client,
        array $config,
    ): RepositoryInterface&LoggerAwareInterface;

    protected function getRepository(array $additionalConfig = []): RepositoryInterface
    {
        $repository = $this->createRepository(
            $this->client,
            array_merge(
                [
                    'index_read'  => self::INDEX['read'],
                    'index_write' => self::INDEX['write'],
                ],
                $additionalConfig
            )
        );

        $repository->setLogger($this->logger);

        return $repository;
    }

    protected function getRepositoryWithEntityClass(): RepositoryInterface
    {
        return $this->getRepository(['entity_class' => PersistableEntityStub::class]);
    }

    protected function getRepositoryWithEntityFactory(): RepositoryInterface
    {
        return $this->getRepository(['entity_factory' => new EntityFactoryStub()]);
    }

    protected function getRepositoryWithEntitySerializer(): RepositoryInterface
    {
        return $this->getRepository(['entity_serializer' => new EntitySerializerStub()]);
    }

    protected function getRepositoryWithForceRefresh(): RepositoryInterface
    {
        return $this->getRepository(['force_refresh_on_write' => true]);
    }

    protected function getRepositoryWithTrackTotalHits(): RepositoryInterface
    {
        return $this->getRepository(['track_total_hits' => true]);
    }

    protected static function mergeQueryAndResultsVariations(array $queryVariations, array $resultsVariations): array
    {
        $allVariations = [];
        foreach ($queryVariations as $queryName => $queryVariation) {
            foreach ($resultsVariations as $resultsName => $resultsVariation) {
                $allVariations[sprintf('%s_%s', $queryName, $resultsName)] = array_merge(
                    $queryVariation,
                    $resultsVariation
                );
            }
        }

        return $allVariations;
    }

    protected static function modifySearchResultDataForEntityUseCases(array $baseData): array
    {
        return array_map(
            function(array $variables) {
                $variables['endResult'] = array_map(
                    function(array $result): PersistableEntityStub {
                        $entity = new PersistableEntityStub();
                        foreach ($result['_source'] as $key => $value) {
                            $entity->{$key} = $value;
                        }
                        $entity->_meta = ['_index' => $result['_index'], '_score' => $result['_score']];

                        return $entity;
                    },
                    $variables['result']['hits']['hits'] ?? []
                );

                return $variables;
            },
            $baseData
        );
    }

    protected static function removeEndResults(array $baseData): array
    {
        array_walk(
            $baseData,
            function(array &$item): void {
                unset($item['endResult']);
            }
        );

        return $baseData;
    }
}
