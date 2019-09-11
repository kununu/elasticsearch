<?php

namespace App\Services\Elasticsearch;

use Elastica\Query;

/**
 * Interface SubmissionManagerInterface
 *
 * @package App\Services\Elasticsearch
 */
interface SubmissionManagerInterface extends ElasticsearchManagerInterface
{
    /**
     * @param string|null $field
     * @param string|null $value
     *
     * @return array
     */
    public function aggregateCultureDataByField(?string $field = null, ?string $value = null): array;

    /**
     * This method is public for testing purposes only.
     *
     * @param string|null $field
     * @param string|null $value
     *
     * @return \Elastica\Query
     */
    public function buildSumAggregationQuery(?string $field, ?string $value): Query;

    /**
     * @param string $profileUuid
     *
     * @return int
     */
    public function countSubmissions(string $profileUuid): int;

    /**
     * This method exists and is public for testing purposes only.
     *
     * @param string $profileUuid
     *
     * @return \Elastica\Query
     */
    public function buildCountByProfileUuidQuery(string $profileUuid): Query;
}