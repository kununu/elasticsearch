<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Filter;

use Kununu\Elasticsearch\Query\Criteria\Filter\Exists;
use PHPUnit\Framework\TestCase;

final class ExistsTest extends TestCase
{
    public function testTrue(): void
    {
        $this->assertEquals(
            [
                'exists' => [
                    'field' => 'field_a',
                ],
            ],
            Exists::asArray('field_a', true)
        );
    }

    public function testFalse(): void
    {
        $this->assertEquals(
            [
                'bool' => [
                    'must_not' => [
                        [
                            'exists' => [
                                'field' => 'field_a',
                            ],
                        ],
                    ],
                ],
            ],
            Exists::asArray('field_a', false)
        );
    }
}
