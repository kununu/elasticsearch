<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Entity\Dimension;
use App\Services\FactorService;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

/**
 * Class SubmissionManager
 *
 * @package App\Services\Elasticsearch
 */
class SubmissionManager extends ElasticsearchManager implements SubmissionManagerInterface
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
            return $this->elasticSearchClient->search($params)['aggregations'];
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
                    'should' => [
                        [
                            'match' => [
                                $field => $value,
                            ],
                        ],
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
     * @inheritdoc
     */
    public function countSubmissions(string $profileUuid): int
    {
        $params = [
            'index' => $this->getIndex(),
            'body' => [
                'query' => [
                    'term' => [
                        'profile_uuid.keyword' => $profileUuid,
                    ],
                ],
            ],
        ];

        try {
            return $this->elasticSearchClient->count($params)['count'];
        } catch (\Exception $e) {
            $this->logErrorAndThrowException($e);
        }
    }
}
