<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\MatchQuery;
use PHPUnit\Framework\TestCase;

final class MatchQueryTest extends TestCase
{
    private const string QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        self::assertEquals(
            [
                'match' => [
                    'field_a' => [
                        'query' => self::QUERY_STRING,
                    ],
                ],
            ],
            MatchQuery::asArray(['field_a'], self::QUERY_STRING)
        );
    }

    public function testMultipleFields(): void
    {
        self::assertEquals(
            [
                'multi_match' => [
                    'fields' => ['field_a', 'field_b'],
                    'query'  => self::QUERY_STRING,
                ],
            ],
            MatchQuery::asArray(['field_a', 'field_b'], self::QUERY_STRING)
        );
    }

    public function testSingleFieldWithOptions(): void
    {
        self::assertEquals(
            [
                'match' => [
                    'field_a' => [
                        'query' => self::QUERY_STRING,
                        'boost' => 42,
                    ],
                ],
            ],
            MatchQuery::asArray(['field_a'], self::QUERY_STRING, ['boost' => 42])
        );
    }

    public function testMultipleFieldsWithOptions(): void
    {
        self::assertEquals(
            [
                'multi_match' => [
                    'fields' => ['field_a', 'field_b'],
                    'query'  => self::QUERY_STRING,
                    'boost'  => 42,
                ],
            ],
            MatchQuery::asArray(['field_a', 'field_b'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}
