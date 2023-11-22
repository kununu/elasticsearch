<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhraseQuery;
use PHPUnit\Framework\TestCase;

final class MatchPhraseQueryTest extends TestCase
{
    protected const QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        $this->assertEquals(
            [
                'match_phrase' => [
                    'field_a' => [
                        'query' => self::QUERY_STRING,
                    ],
                ],
            ],
            MatchPhraseQuery::asArray(['field_a'], self::QUERY_STRING)
        );
    }

    public function testSingleFieldWithOptions(): void
    {
        $this->assertEquals(
            [
                'match_phrase' => [
                    'field_a' => [
                        'query' => self::QUERY_STRING,
                        'boost' => 42,
                    ],
                ],
            ],
            MatchPhraseQuery::asArray(['field_a'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}
