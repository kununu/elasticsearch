<?php

namespace App\Services\Elasticsearch;

interface ElasticSubmissionRepositoryInterface extends ElasticsearchRepositoryInterface
{
    /**
     * @param string|null $field
     * @param string|null $value
     *
     * @return array
     */
    public function aggregateCultureDataByField(?string $field = null, ?string $value = null): array;

    /**
     * @param string $profileUuid
     *
     * @return int
     */
    public function countSubmissions(string $profileUuid): int;
}
