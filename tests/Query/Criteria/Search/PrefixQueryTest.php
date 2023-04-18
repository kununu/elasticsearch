<?php
declare(strict_types=1);

namespace Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\PrefixQuery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PrefixQueryTest extends MockeryTestCase
{
    protected const QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        $this->assertEquals(
            [
                'prefix' => [
                    'field_a' => [
                        'value' => self::QUERY_STRING,
                    ],
                ],
            ],
            PrefixQuery::asArray(['field_a'], self::QUERY_STRING)
        );
    }

    public function testSingleFieldWithOptions(): void
    {
        $this->assertEquals(
            [
                'prefix' => [
                    'field_a' => [
                        'value' => self::QUERY_STRING,
                        'boost' => 42,
                    ],
                ],
            ],
            PrefixQuery::asArray(['field_a'], self::QUERY_STRING, ['boost' => 42])
        );
    }
}
