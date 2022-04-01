<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\QueryStringQuery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class QueryStringQueryTest extends MockeryTestCase
{
    protected const QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        $this->assertEquals(
            [
                'query_string' => [
                    'fields' => ['field_a'],
                    'query' => self::QUERY_STRING,
                ],
            ],
            QueryStringQuery::asArray(['field_a'], self::QUERY_STRING)
        );
    }

    public function testMultipleFields(): void
    {
        $this->assertEquals(
            [
                'query_string' => [
                    'fields' => ['field_a', 'field_b'],
                    'query' => self::QUERY_STRING,
                ],
            ],
            QueryStringQuery::asArray(['field_a', 'field_b'], self::QUERY_STRING)
        );
    }

    public function testWithOptions(): void
    {
        $this->assertEquals(
            [
                'query_string' => [
                    'fields' => ['field_a', 'field_b'],
                    'query' => self::QUERY_STRING,
                    'boost' => 42,
                ],
            ],
            QueryStringQuery::asArray(['field_a', 'field_b'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}