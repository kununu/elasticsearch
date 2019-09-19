<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Entity\Dimension;
use App\Entity\Submission;
use App\Services\Elasticsearch\Manager\ElasticsearchManager;
use App\Services\Elasticsearch\Query\Query;
use App\Services\FactorService;
use Elastica\Aggregation\Sum;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\Range;
use Elastica\Query\Term;

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
    public function aggregateCultureDataByField(?string $filterField = null, ?string $filterValue = null): array
    {
        return $this->aggregateByQuery($this->buildSumAggregationQuery($filterField, $filterValue));
    }

    /**
     * This method is public for testing purposes only.
     *
     * @param string|null $filterField
     * @param string|null $filterValue
     *
     * @return \App\Services\Elasticsearch\Query\Query
     */
    public function buildSumAggregationQuery(?string $filterField, ?string $filterValue): Query
    {
        if (!is_numeric($filterValue)) {
            $filterField = $filterField . '.keyword';
        }

        $boolQuery = (new BoolQuery())->addMust($this->buildQueryPartToConsiderOnlyCompletedSubmissions());
        if ($filterField && $filterValue) {
            $boolQuery->addMust(new Match($filterField, $filterValue));
        }
        $query = Query::create($boolQuery);

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
     * @return \App\Services\Elasticsearch\Query\Query
     */
    public function buildCountByProfileUuidQuery(string $profileUuid): Query
    {
        return Query::create(
            (new BoolQuery())
                ->addMust($this->buildQueryPartToConsiderOnlyCompletedSubmissions())
                ->addMust((new Term())->setTerm('profile_uuid.keyword', $profileUuid))
        );
    }

    /**
     * @return \Elastica\Query\AbstractQuery
     */
    protected function buildQueryPartToConsiderOnlyCompletedSubmissions(): AbstractQuery
    {
        return (new Range(
            'dimensions_completed',
            ['gte' => Submission::CONSIDER_FOR_AGGREGATIONS_WHEN_NUMBER_OF_DIMENSIONS]
        ));
    }
}
