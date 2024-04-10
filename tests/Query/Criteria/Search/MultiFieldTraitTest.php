<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\MultiFieldTrait;
use PHPUnit\Framework\TestCase;

final class MultiFieldTraitTest extends TestCase
{
    /** @dataProvider boostPreparationDataProvider */
    public function testBoostPreparation(array $input, array $expectedOutput): void
    {
        $this->assertEquals($expectedOutput, $this->getMultiFieldCapableObject()->publiclyPrepare($input));
    }

    public static function boostPreparationDataProvider(): array
    {
        return [
            'single field, without boost'             => [
                'input'          => ['my_field'],
                'expectedOutput' => ['my_field'],
            ],
            'multiple fields, without boost'          => [
                'input'          => ['field_a', 'field_b'],
                'expectedOutput' => ['field_a', 'field_b'],
            ],
            'single field, with boost'                => [
                'input'          => ['my_field' => ['boost' => 7]],
                'expectedOutput' => ['my_field^7'],
            ],
            'multiple fields, with boost'             => [
                'input'          => ['field_a' => ['boost' => 7], 'field_b' => ['boost' => 42]],
                'expectedOutput' => ['field_a^7', 'field_b^42'],
            ],
            'multiple fields, with and without boost' => [
                'input'          => ['field_a' => ['boost' => 7], 'field_b'],
                'expectedOutput' => ['field_a^7', 'field_b'],
            ],
        ];
    }

    private function getMultiFieldCapableObject(): object
    {
        return new class() {
            use MultiFieldTrait;

            public function publiclyPrepare(array $fields): array
            {
                return $this->prepareFields($fields);
            }
        };
    }
}
