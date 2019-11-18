<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Search;

use App\Services\Elasticsearch\Query\Criteria\Search\QueryString;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class QueryStringTest extends MockeryTestCase
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
            QueryString::asArray(['field_a'], self::QUERY_STRING)
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
            QueryString::asArray(['field_a', 'field_b'], self::QUERY_STRING)
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
            QueryString::asArray(['field_a', 'field_b'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}
