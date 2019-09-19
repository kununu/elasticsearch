<?php

namespace App\Tests\Unit\Services\Elasticsearch;

use App\Entity\Submission;
use App\Services\Elasticsearch\ElasticSubmissionRepository;
use App\Services\Elasticsearch\ElasticSubmissionRepositoryInterface;
use App\Services\Elasticsearch\Exception\ElasticsearchException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticSubmissionRepositoryTest extends MockeryTestCase
{
    use ElasticsearchRepositoryTestTrait;

    protected const INDEX = 'some_index';
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const PROFILE_UUID = 'd547f967-523c-4788-a038-d7b9a3f2d5f6';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';

    /**
     * @return array
     */
    public function aggregationData(): array
    {
        $buildExpectedQuery = function (bool $hasQuery, ?string $expectedMatchField = null, $matchValue = null): array {
            $expectedQuery = [
                'index' => self::INDEX,
                'body' => [
                    'aggs' => [
                        'classic_culture_dimension_leadership' => [
                            'sum' => [
                                'field' => 'classic.CULTURE_DIMENSION_LEADERSHIP',
                            ],
                        ],
                        'classic_culture_dimension_strategic_direction' => [
                            'sum' => [
                                'field' => 'classic.CULTURE_DIMENSION_STRATEGIC_DIRECTION',
                            ],
                        ],
                        'classic_culture_dimension_work_life' => [
                            'sum' => [
                                'field' => 'classic.CULTURE_DIMENSION_WORK_LIFE',
                            ],
                        ],
                        'classic_culture_dimension_working_together' => [
                            'sum' => [
                                'field' => 'classic.CULTURE_DIMENSION_WORKING_TOGETHER',
                            ],
                        ],
                        'new_work_culture_dimension_leadership' => [
                            'sum' => [
                                'field' => 'new_work.CULTURE_DIMENSION_LEADERSHIP',
                            ],
                        ],
                        'new_work_culture_dimension_strategic_direction' => [
                            'sum' => [
                                'field' => 'new_work.CULTURE_DIMENSION_STRATEGIC_DIRECTION',
                            ],
                        ],
                        'new_work_culture_dimension_work_life' => [
                            'sum' => [
                                'field' => 'new_work.CULTURE_DIMENSION_WORK_LIFE',
                            ],
                        ],
                        'new_work_culture_dimension_working_together' => [
                            'sum' => [
                                'field' => 'new_work.CULTURE_DIMENSION_WORKING_TOGETHER',
                            ],
                        ],
                    ],
                ],
            ];

            if ($hasQuery) {
                $expectedQuery['body']['query'] = [
                    'bool' => [
                        'must' => [
                            [
                                'match' => [
                                    $expectedMatchField => $matchValue,
                                ],
                            ],
                            [
                                'range' => [
                                    'dimensions_completed' => [
                                        'gte' => Submission::CONSIDER_FOR_AGGREGATIONS_WHEN_NUMBER_OF_DIMENSIONS,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            return $expectedQuery;
        };

        return [
            'filter on numeric field' => [
                'field' => 'profile_id',
                'value' => 12345,
                'expected_query' => $buildExpectedQuery(true, 'profile_id', 12345),
            ],
            'filter on alphanumeric field' => [
                'field' => 'profile_uuid',
                'value' => self::PROFILE_UUID,
                'expected_query' => $buildExpectedQuery(true, 'profile_uuid.keyword', self::PROFILE_UUID),
            ],
            'empty filter' => [
                'field' => 'profile_id',
                'value' => null,
                'expected_query' => $buildExpectedQuery(false),
            ],
        ];
    }

    /**
     * @dataProvider aggregationData
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $expectedQuery
     */
    public function testAggregateCultureDataByField(string $field, $value, array $expectedQuery): void
    {
        $this->elasticsearchClientMock
            ->shouldReceive('search')
            ->once()
            ->with($expectedQuery)
            ->andReturn(['aggregations' => ['Let\'s assume it is aggregated data']]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->aggregateCultureDataByField(
            $field,
            $value
        );
    }

    public function testAggregateCultureDataByFieldFails(): void
    {
        $this->elasticsearchClientMock
            ->shouldReceive('search')
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->aggregateCultureDataByField('profile_id', 12345);
    }

    public function testCountSubmissionsByProfileUuid(): void
    {
        $expectedParams = [
            'index' => self::INDEX,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'profile_uuid.keyword' => self::PROFILE_UUID,
                                ],
                            ],
                            [
                                'range' => [
                                    'dimensions_completed' => [
                                        'gte' => Submission::CONSIDER_FOR_AGGREGATIONS_WHEN_NUMBER_OF_DIMENSIONS,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->elasticsearchClientMock
            ->shouldReceive('count')
            ->once()
            ->with($expectedParams)
            ->andReturn(['count' => 7]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->countSubmissions(self::PROFILE_UUID);
    }

    public function testCountSubmissionsByProfileUuidFails(): void
    {
        $this->elasticsearchClientMock
            ->shouldReceive('count')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->countSubmissions(self::PROFILE_UUID);
    }

    /**
     * @return \App\Services\Elasticsearch\ElasticSubmissionRepositoryInterface
     */
    private function getManager(): ElasticSubmissionRepositoryInterface
    {
        return new ElasticSubmissionRepository($this->elasticsearchClientMock, $this->loggerMock, self::INDEX);
    }
}
