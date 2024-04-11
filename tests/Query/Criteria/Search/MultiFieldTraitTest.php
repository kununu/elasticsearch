<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Query\Criteria\Search;

use Kununu\Elasticsearch\Query\Criteria\Search\MultiFieldTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MultiFieldTraitTest extends TestCase
{
    #[DataProvider('boostPreparationDataProvider')]
    public function testBoostPreparation(array $input, array $expectedOutput): void
    {
        self::assertEquals($expectedOutput, $this->getMultiFieldCapableObject()->prepareFields($input));
    }

    public static function boostPreparationDataProvider(): array
    {
        return [
            'single_field_without_boost'             => [
                'input'          => ['my_field'],
                'expectedOutput' => ['my_field'],
            ],
            'multiple_fields_without_boost'          => [
                'input'          => ['field_a', 'field_b'],
                'expectedOutput' => ['field_a', 'field_b'],
            ],
            'single_field_with_boost'                => [
                'input'          => ['my_field' => ['boost' => 7]],
                'expectedOutput' => ['my_field^7'],
            ],
            'multiple_fields_with_boost'             => [
                'input'          => ['field_a' => ['boost' => 7], 'field_b' => ['boost' => 42]],
                'expectedOutput' => ['field_a^7', 'field_b^42'],
            ],
            'multiple_fields_with_and_without_boost' => [
                'input'          => ['field_a' => ['boost' => 7], 'field_b'],
                'expectedOutput' => ['field_a^7', 'field_b'],
            ],
        ];
    }

    private function getMultiFieldCapableObject(): object
    {
        return new class() {
            use MultiFieldTrait {
                prepareFields as traitPrepareFields;
            }

            public function prepareFields(array $fields): array
            {
                return self::traitPrepareFields($fields);
            }
        };
    }
}
