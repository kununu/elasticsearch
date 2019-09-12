<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

class Query extends \Elastica\Query implements QueryInterface
{
    /**
     * @return array
     */
    public function toArray(): array
    {
        return parent::toArray();
    }
}