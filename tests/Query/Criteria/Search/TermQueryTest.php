<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\TermQuery;
use PHPUnit\Framework\TestCase;

final class TermQueryTest extends TestCase
{
    private const TERM = 'what was i looking for?';
    private const FIELD = 'field_a';

    public function testSingleField(): void
    {
        self::assertEquals(
            [
                'term' => [
                    self::FIELD => [
                        'value' => self::TERM,
                    ],
                ],
            ],
            TermQuery::asArray(self::FIELD, self::TERM)
        );
    }

    public function testSingleFieldWithOptions(): void
    {
        self::assertEquals(
            [
                'term' => [
                    self::FIELD => [
                        'value' => self::TERM,
                        'boost' => 42,
                    ],
                ],
            ],
            TermQuery::asArray(self::FIELD, self::TERM, ['boost' => 42])
        );
    }
}
