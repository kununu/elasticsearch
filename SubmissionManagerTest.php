<?php

namespace App\Tests\Unit\Services\Elasticsearch;

use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\SubmissionManager;
use App\Services\Elasticsearch\SubmissionManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class SubmissionManagerTest extends MockeryTestCase
{
    use ElasticsearchManagerTestTrait;

    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const PROFILE_UUID = 'd547f967-523c-4788-a038-d7b9a3f2d5f6';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';
    protected const DOCUMENT_COUNT = 42;

    public function testAggregateCultureDataByField(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('aggregate')
            ->once();

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->aggregateCultureDataByField('profile_id', 12345);
    }

    public function testAggregateCultureDataByFieldFails(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('aggregate')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->aggregateCultureDataByField('profile_id', 12345);
    }

    /**
     * @return array
     */
    public function aggregationData(): array
    {
        $buildExpectedQuery = function (bool $hasQuery, ?string $expectedMatchField = null, $matchValue = null): array {
            $expectedQuery = [
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
            ];

            if ($hasQuery) {
                $expectedQuery['query'] = [
                    'bool' => [
                        'should' => [
                            [
                                'match' => [
                                    $expectedMatchField => $matchValue,
                                ],
                            ],
                        ],
                    ],
                ];
            } else {
                $expectedQuery['query'] = [
                    'match_all' => new \stdClass(),
                ];
            }

            return $expectedQuery;
        };

        return [
            ['profile_id', 12345, $buildExpectedQuery(true, 'profile_id', 12345)],
            ['profile_uuid', self::PROFILE_UUID, $buildExpectedQuery(true, 'profile_uuid.keyword', self::PROFILE_UUID)],
            ['profile_id', null, $buildExpectedQuery(false)],
        ];
    }

    /**
     * @dataProvider aggregationData
     *
     * @param string $matchField
     * @param        $matchValue
     * @param array  $expectedQuery
     */
    public function testBuildSumAggregationQuery(string $matchField, $matchValue, array $expectedQuery): void
    {
        $query = $this->getManager()->buildSumAggregationQuery($matchField, $matchValue);
        $this->assertEquals($expectedQuery, $query->toArray());
    }

    public function testCountSubmissionsByProfileUuid(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('count')
            ->once()
            ->andReturn(self::DOCUMENT_COUNT);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals(self::DOCUMENT_COUNT, $this->getManager()->countSubmissions(self::PROFILE_UUID));
    }

    public function testCountSubmissionsByProfileUuidFails(): void
    {
        $this->elasticaAdapterMock
            ->shouldReceive('count')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->countSubmissions(self::PROFILE_UUID);
    }

    public function testBuildCountByProfileUuidQuery(): void
    {
        $query = $this->getManager()->buildCountByProfileUuidQuery(self::PROFILE_UUID);

        $expected = [
            'query' => [
                'term' => [
                    'profile_uuid.keyword' => [
                        'value' => self::PROFILE_UUID,
                        'boost' => 1.0,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
    }

    /**
     * @return \App\Services\Elasticsearch\SubmissionManagerInterface
     */
    private function getManager(): SubmissionManagerInterface
    {
        return new SubmissionManager($this->elasticaAdapterMock, $this->loggerMock);
    }
}
