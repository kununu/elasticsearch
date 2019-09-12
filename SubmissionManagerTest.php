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

    protected const INDEX = 'some_index';
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const PROFILE_UUID = 'd547f967-523c-4788-a038-d7b9a3f2d5f6';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';

    /**
     * @return array
     */
    public function aggregationData(): array
    {
        return [
            ['profile_id', 12345, 'profile_id'],
            ['profile_uuid', self::PROFILE_UUID, 'profile_uuid.keyword'],
        ];
    }

    /**
     * @dataProvider aggregationData
     *
     * @param string $field
     * @param mixed  $value
     * @param string $expectedMatchField
     */
    public function testAggregateCultureDataByField(string $field, $value, string $expectedMatchField): void
    {
        $expectedParams = [
            'index' => self::INDEX,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'match' => [
                                    $expectedMatchField => $value,
                                ],
                            ],
                        ],
                    ],
                ],
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

        $this->elasticsearchClientMock
            ->shouldReceive('search')
            ->once()
            ->with($expectedParams)
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
                    'term' => [
                        'profile_uuid.keyword' => self::PROFILE_UUID,
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
     * @return \App\Services\Elasticsearch\SubmissionManagerInterface
     */
    private function getManager(): SubmissionManagerInterface
    {
        return new SubmissionManager($this->elasticsearchClientMock, $this->loggerMock, self::INDEX);
    }
}
