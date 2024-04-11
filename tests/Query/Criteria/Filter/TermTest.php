<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\Term;
use PHPUnit\Framework\TestCase;

final class TermTest extends TestCase
{
    public function testWithoutOptions(): void
    {
        self::assertEquals(
            [
                'term' => [
                    'field_a' => 'foo',
                ],
            ],
            Term::asArray('field_a', 'foo')
        );
    }

    public function testWithOptions(): void
    {
        self::assertEquals(
            [
                'term' => [
                    'field_a' => [
                        'value' => 'foo',
                        'boost' => 7,
                    ],
                ],
            ],
            Term::asArray('field_a', 'foo', ['boost' => 7])
        );
    }
}
