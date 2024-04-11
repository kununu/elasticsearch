<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Client;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Query\RawQuery;
use Kununu\Elasticsearch\Repository\Repository;
use Kununu\Elasticsearch\Repository\RepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class AbstractRepositoryTestCase extends TestCase
{
    public const ID = 'can_be_anything';
    public const ID_2 = 'can_also_be_anything';

    protected const INDEX = [
        'read'  => 'some_index_read',
        'write' => 'some_index_write',
    ];
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const ERROR_MESSAGE = 'Any error';
    protected const DOCUMENT_COUNT = 42;
    protected const SCROLL_ID = 'DnF1ZXJ5VGhlbkZldGNoBQAAAAAAAAFbFkJVNEdjZWVjU';

    protected MockObject&Client $clientMock;
    protected MockObject&LoggerInterface $loggerMock;

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
        return self::mergeQueryAndResultsVariations(self::queriesDataProvider(), self::searchResultDataProvider());
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
                'es_result'  => [
                    'hits' => [
                        'total' => [
                            'value' => self::DOCUMENT_COUNT,
                        ],
                        'hits'  => [
                        ],
                    ],
                ],
                'end_result' => [],
            ],
            'one_result'  => [
                'es_result'  => [
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
                'end_result' => [
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
                'es_result'  => [
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
                'end_result' => [
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
                    $newCase['es_result']['_scroll_id'] = self::SCROLL_ID;
                }
                $newCase['scroll'] = $scroll;

                $allVariations[$fullCaseName] = $newCase;
            }
        }

        return $allVariations;
    }

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    protected function getRepository(array $additionalConfig = []): RepositoryInterface
    {
        $repo = new Repository(
            $this->clientMock,
            array_merge(
                [
                    'index_read'  => self::INDEX['read'],
                    'index_write' => self::INDEX['write'],
                ],
                $additionalConfig
            )
        );

        $repo->setLogger($this->loggerMock);

        return $repo;
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
                $variables['end_result'] = array_map(
                    function(array $result): PersistableEntityStub {
                        $entity = new PersistableEntityStub();
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
}
