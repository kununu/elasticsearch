<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\PrefixQuery;
use PHPUnit\Framework\TestCase;

final class PrefixQueryTest extends TestCase
{
    private const QUERY_STRING = 'what was i looking for?';

    public function testSingleField(): void
    {
        self::assertEquals(
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
        self::assertEquals(
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
