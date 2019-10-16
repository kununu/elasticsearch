<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Filter;

use App\Services\Elasticsearch\Query\Criteria\Filter\Exists;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ExistsTest extends MockeryTestCase
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
