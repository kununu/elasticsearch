<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

interface QueryInterface
{
    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param mixed $query
     *
     * @return \App\Services\Elasticsearch\Query\QueryInterface
     */
    public static function create($query);
}
