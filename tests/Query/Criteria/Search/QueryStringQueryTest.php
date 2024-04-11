<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\QueryStringQuery;
use PHPUnit\Framework\TestCase;

final class QueryStringQueryTest extends TestCase
{
    private const QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        self::assertEquals(
            [
                'query_string' => [
                    'fields' => ['field_a'],
                    'query'  => self::QUERY_STRING,
                ],
            ],
            QueryStringQuery::asArray(['field_a'], self::QUERY_STRING)
        );
    }

    public function testMultipleFields(): void
    {
        self::assertEquals(
            [
                'query_string' => [
                    'fields' => ['field_a', 'field_b'],
                    'query'  => self::QUERY_STRING,
                ],
            ],
            QueryStringQuery::asArray(['field_a', 'field_b'], self::QUERY_STRING)
        );
    }

    public function testWithOptions(): void
    {
        self::assertEquals(
            [
                'query_string' => [
                    'fields' => ['field_a', 'field_b'],
                    'query'  => self::QUERY_STRING,
                    'boost'  => 42,
                ],
            ],
            QueryStringQuery::asArray(['field_a', 'field_b'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}
