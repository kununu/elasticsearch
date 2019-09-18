<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

final class SortDirection
{
    public const ASC = 'asc';
    public const DESC = 'desc';

    /**
     * @return array
     */
    public static function all(): array
    {
        return [static::ASC, static::DESC];
    }
}
