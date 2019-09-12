<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Entity\Dimension;
use App\Services\FactorService;
use Elastica\Aggregation\Sum;
use Elastica\Query;

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
        return $this->aggregateByQuery($this->buildSumAggregationQuery($field, $value));
    }

    /**
     * This method is public for testing purposes only.
     *
     * @param string|null $field
     * @param string|null $value
     *
     * @return \Elastica\Query
     */
    public function buildSumAggregationQuery(?string $field, ?string $value): Query
    {
        if (!is_numeric($value)) {
            $field = $field . '.keyword';
        }

        $query = Query::create(
            $field && $value
                ? (new Query\BoolQuery())->addShould(new Query\Match($field, $value))
                : null
        );

        foreach (FactorService::TYPES as $type) {
            foreach (Dimension::DIMENSIONS as $dimension) {
                $aggregationName = sprintf(
                    '%s_%s',
                    $type,
                    mb_strtolower($dimension)
                );
                $aggregationField = sprintf(
                    '%s.%s',
                    $type,
                    $dimension
                );

                $query->addAggregation((new Sum($aggregationName))->setField($aggregationField));
            }
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function countSubmissions(string $profileUuid): int
    {
        return $this->countByQuery($this->buildCountByProfileUuidQuery($profileUuid));
    }

    /**
     * This method exists and is public for testing purposes only.
     *
     * @param string $profileUuid
     *
     * @return \Elastica\Query
     */
    public function buildCountByProfileUuidQuery(string $profileUuid): Query
    {
        return Query::create(
            (new Query\Term())->setTerm('profile_uuid.keyword', $profileUuid)
        );
    }
}
