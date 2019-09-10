<?php

namespace App\Services\Elasticsearch;

/**
 * Interface SubmissionManagerInterface
 *
 * @package App\Services\Elasticsearch
 */
interface SubmissionManagerInterface extends ElasticsearchManagerInterface
{
    /**
     * @inheritdoc
     */
    public function aggregateCultureDataByField(?string $field = null, ?string $value = null): array;

    /**
     * @inheritdoc
     */
    public function countSubmissions(string $profileUuid): int;
}