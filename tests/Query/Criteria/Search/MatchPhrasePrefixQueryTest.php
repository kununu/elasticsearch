<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhrasePrefixQuery;
use PHPUnit\Framework\TestCase;

final class MatchPhrasePrefixQueryTest extends TestCase
{
    protected const QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        $this->assertEquals(
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
        $this->assertEquals(
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
