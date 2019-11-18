<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\MultiFieldTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class MultiFieldTraitTest extends MockeryTestCase
{
    /**
     * @return object
     */
    public function getMultiFieldCapableObject(): object
    {
        return new class
        {
            use MultiFieldTrait;

            public function publiclyPrepare(array $fields): array
            {
                return $this->prepareFields($fields);
            }
        };
    }

    public function boostPreparationData(): array
    {
        return [
            'single field, without boost' => [
                'input' => ['my_field'],
                'expectedOutput' => ['my_field'],
            ],
            'multiple fields, without boost' => [
                'input' => ['field_a', 'field_b'],
                'expectedOutput' => ['field_a', 'field_b'],
            ],
            'single field, with boost' => [
                'input' => ['my_field' => ['boost' => 7]],
                'expectedOutput' => ['my_field^7'],
            ],
            'multiple fields, with boost' => [
                'input' => ['field_a' => ['boost' => 7], 'field_b' => ['boost' => 42]],
                'expectedOutput' => ['field_a^7', 'field_b^42'],
            ],
            'multiple fields, with and without boost' => [
                'input' => ['field_a' => ['boost' => 7], 'field_b'],
                'expectedOutput' => ['field_a^7', 'field_b'],
            ],
        ];
    }

    /**
     * @dataProvider boostPreparationData
     *
     * @param array $input
     * @param array $expectedOutput
     */
    public function testBoostPreparation(array $input, array $expectedOutput): void
    {
        $this->assertEquals($expectedOutput, $this->getMultiFieldCapableObject()->publiclyPrepare($input));
    }
}
