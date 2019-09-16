<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

use Elastica\Exception\InvalidException;
use Elastica\Query\AbstractQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\QueryString;
use Elastica\Suggest;
use Elastica\Suggest\AbstractSuggest;

class Query extends \Elastica\Query implements QueryInterface
{
    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return parent::toArray();
    }

    /**
     * Need to override this method from \Elastica\Query to make use of late static binding.
     *
     * @inheritdoc
     *
     * @return \App\Services\Elasticsearch\Query\Query
     */
    public static function create($query): Query
    {
        switch (true) {
            case $query instanceof self:
                return $query;
            case $query instanceof AbstractQuery:
            case is_array($query):
            case $query instanceof Suggest:
                return new static($query);
            case empty($query):
                return new static(new MatchAll());
            case is_string($query):
                return new static(new QueryString($query));
            case $query instanceof AbstractSuggest:
                return new static(new Suggest($query));
        }

        throw new InvalidException('Unexpected argument to create a query for.');
    }
}
