<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

interface QueryInterface
{
    /**
     * @return array
     */
    public function toArray(): array;
}