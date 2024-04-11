<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhrasePrefixQuery;
use PHPUnit\Framework\TestCase;

final class MatchPhrasePrefixQueryTest extends TestCase
{
    private const QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        self::assertEquals(
            [
                'match_phrase_prefix' => [
                    'field_a' => [
                        'query' => self::QUERY_STRING,
                    ],
                ],
            ],
            MatchPhrasePrefixQuery::asArray(['field_a'], self::QUERY_STRING)
        );
    }

    public function testSingleFieldWithOptions(): void
    {
        self::assertEquals(
            [
                'match_phrase_prefix' => [
                    'field_a' => [
                        'query' => self::QUERY_STRING,
                        'boost' => 42,
                    ],
                ],
            ],
            MatchPhrasePrefixQuery::asArray(['field_a'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}
