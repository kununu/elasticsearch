<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Entity\Dimension;
use App\Entity\Submission;
use App\Services\FactorService;

class ElasticSubmissionRepository extends ElasticsearchRepository implements ElasticSubmissionRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function aggregateCultureDataByField(?string $field = null, ?string $value = null): array
    {
        $params = [
            'index' => $this->getIndex(),
            'body' => $this->getSumAggregationQuery($field, $value),
        ];

        try {
            return $this->elasticsearchClient->search($params)['aggregations'];
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }

    /**
     * @param string|null $field
     * @param string|null $value
     *
     * @return array
     */
    protected function getSumAggregationQuery(?string $field, ?string $value): array
    {
        if (!is_numeric($value)) {
            $field = $field . '.keyword';
        }

        $query = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                $field => $value,
                            ],
                        ],
                        $this->buildQueryPartForOnlyCompletedSubmissions(),
                    ],
                ],
            ],
        ];

        if (!$field || !$value) {
            $query = [];
        }

        $aggregation = [];

        foreach (FactorService::TYPES as $type) {
            foreach (Dimension::DIMENSIONS as $dimension) {
                $key = sprintf(
                    '%s_%s',
                    $type,
                    mb_strtolower($dimension)
                );

                $aggregation['aggs'][$key] = [
                    'sum' => [
                        'field' => sprintf(
                            '%s.%s',
                            $type,
                            $dimension
                        ),
                    ],
                ];
            }
        }

        return array_merge($query, $aggregation);
    }

    /**
     * @return array
     */
    private function buildQueryPartForOnlyCompletedSubmissions(): array
    {
        return [
            'range' => [
                'dimensions_completed' => [
                    'gte' => Submission::CONSIDER_FOR_AGGREGATIONS_WHEN_NUMBER_OF_DIMENSIONS,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function countSubmissions(string $profileUuid): int
    {
        $params = [
            'index' => $this->getIndex(),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'profile_uuid.keyword' => $profileUuid,
                                ],
                            ],
                            $this->buildQueryPartForOnlyCompletedSubmissions(),
                        ],
                    ],
                ],
            ],
        ];

        try {
            return $this->elasticsearchClient->count($params)['count'];
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }
}
