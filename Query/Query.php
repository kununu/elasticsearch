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
    public static function create($query = null): Query
    {
        switch (true) {
            case $query instanceof self:
                $ret = $query;
                break;
            case $query instanceof AbstractQuery:
            case is_array($query):
            case $query instanceof Suggest:
                $ret = new static($query);
                break;
            case empty($query):
                $ret = new static(new MatchAll());
                break;
            case is_string($query):
                $ret = new static(new QueryString($query));
                break;
            case $query instanceof AbstractSuggest:
                $ret = new static(new Suggest($query));
                break;
            default:
                throw new InvalidException('Unexpected argument to create a query for.');
        }

        return $ret;
    }
}
