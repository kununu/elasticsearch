<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\WildcardQuery;
use PHPUnit\Framework\TestCase;

final class WildcardQueryTest extends TestCase
{
    private const string TERM = 'the qu?ck brown fox*';
    private const string FIELD = 'field_a';

    public function testSingleField(): void
    {
        self::assertEquals(
            [
                'wildcard' => [
                    self::FIELD => [
                        'value' => self::TERM,
                    ],
                ],
            ],
            WildcardQuery::asArray(self::FIELD, self::TERM)
        );
    }

    public function testSingleFieldWithOptions(): void
    {
        self::assertEquals(
            [
                'wildcard' => [
                    self::FIELD => [
                        'value'   => self::TERM,
                        'boost'   => 42,
                        'rewrite' => 'constant_score',
                    ],
                ],
            ],
            WildcardQuery::asArray(self::FIELD, self::TERM, ['boost' => 42, 'rewrite' => 'constant_score'])
        );
    }
}
