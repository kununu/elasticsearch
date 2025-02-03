<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

final class Search extends AbstractSearch
{
    public static function create(
        array $fields,
        string $queryString,
        string $type = self::QUERY_STRING,
        array $options = [],
    ): Search {
        return new self($fields, $queryString, $type, $options);
    }
}
