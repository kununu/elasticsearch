<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\Term;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class TermTest extends MockeryTestCase
{
    protected const TERM = 'what was i looking for?';
    protected const FIELD = 'field_a';

    public function testSingleField(): void
    {
        $this->assertEquals(
            [
                'term' => [
                    self::FIELD => [
                        'value' => self::TERM,
                    ],
                ],
            ],
            Term::asArray(self::FIELD, self::TERM)
        );
    }

    public function testSingleFieldWithOptions(): void
    {
        $this->assertEquals(
            [
                'term' => [
                    self::FIELD => [
                        'value' => self::TERM,
                        'boost' => 42,
                    ],
                ],
            ],
            Term::asArray(self::FIELD, self::TERM, ['boost' => 42])
        );
    }
}
