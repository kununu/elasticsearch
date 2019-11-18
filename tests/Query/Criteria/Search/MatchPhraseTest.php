<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhrase;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class MatchPhraseTest extends MockeryTestCase
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
            MatchPhrase::asArray(['field_a'], self::QUERY_STRING)
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
            MatchPhrase::asArray(['field_a'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}
