<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\Match;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class MatchTest extends MockeryTestCase
{
    protected const QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        $this->assertEquals(
            [
                'match' => [
                    'field_a' => [
                        'query' => self::QUERY_STRING,
                    ],
                ],
            ],
            Match::asArray(['field_a'], self::QUERY_STRING)
        );
    }

    public function testMultipleFields(): void
    {
        $this->assertEquals(
            [
                'multi_match' => [
                    'fields' => ['field_a', 'field_b'],
                    'query' => self::QUERY_STRING,
                ],
            ],
            Match::asArray(['field_a', 'field_b'], self::QUERY_STRING)
        );
    }

    public function testSingleFieldWithOptions(): void
    {
        $this->assertEquals(
            [
                'match' => [
                    'field_a' => [
                        'query' => self::QUERY_STRING,
                        'boost' => 42,
                    ],
                ],
            ],
            Match::asArray(['field_a'], self::QUERY_STRING, ['boost' => 42])
        );
    }

    public function testMultipleFieldsWithOptions(): void
    {
        $this->assertEquals(
            [
                'multi_match' => [
                    'fields' => ['field_a', 'field_b'],
                    'query' => self::QUERY_STRING,
                    'boost' => 42,
                ],
            ],
            Match::asArray(['field_a', 'field_b'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}
