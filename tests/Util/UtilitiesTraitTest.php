<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Util\UtilitiesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UtilitiesTraitTest extends TestCase
{
    use UtilitiesTrait;

    #[DataProvider('filterNullAndEmptyValuesDataProvider')]
    public function testFilterNullAndEmptyValues(array $data, bool $recursive, array $expected): void
    {
        self::assertEquals($expected, self::filterNullAndEmptyValues($data, $recursive));
    }

    public static function filterNullAndEmptyValuesDataProvider(): array
    {
        return [
            'non_recursive' => [
                [
                    'a' => 1,
                    'b' => [
                        'c' => null,
                        'd' => [
                            'e' => null,
                            'f' => 4,
                            'g' => [
                                'i' => null,
                                'j' => false,
                                'k' => 0,
                                'l' => '',
                            ],
                        ],
                    ],
                    'h' => null,
                    'i' => [],
                    'j' => '',
                ],
                false,
                [
                    'a' => 1,
                    'b' => [
                        'c' => null,
                        'd' => [
                            'e' => null,
                            'f' => 4,
                            'g' => [
                                'i' => null,
                                'j' => false,
                                'k' => 0,
                                'l' => '',
                            ],
                        ],
                    ],
                    'j' => '',
                ],
            ],
            'recursive'     => [
                [
                    'a' => 1,
                    'b' => [
                        'c' => null,
                        'd' => [
                            'e' => null,
                            'f' => 4,
                            'g' => [
                                'i' => null,
                                'j' => false,
                                'k' => 0,
                                'l' => '',
                            ],
                        ],
                    ],
                    'h' => null,
                ],
                true,
                [
                    'a' => 1,
                    'b' => [
                        'd' => [
                            'f' => 4,
                            'g' => [
                                'j' => false,
                                'k' => 0,
                                'l' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('formatMultipleDataProvider')]
    public function testFormatMultiple(
        string $separator,
        string $itemMask,
        string $expected,
        int|float|string ...$values,
    ): void {
        $result = self::formatMultiple($separator, $itemMask, ...$values);

        self::assertEquals($expected, $result);
    }

    public static function formatMultipleDataProvider(): array
    {
        return [
            'no_values'    => [
                ',',
                '%s',
                '',
            ],
            'single_value' => [
                '#',
                '%d',
                '100',
                100,
            ],
            'string_mask'  => [
                ',',
                '%s',
                '1,2.5,value',
                1,
                2.5,
                'value',
            ],
            'int_mask'     => [
                ',',
                '%d',
                '1,2,0',
                1,
                2.5,
                'value',
            ],
            'float_mask'   => [
                ';',
                '%.2f',
                '1.00;2.50;0.00',
                1,
                2.5,
                'value',
            ],
        ];
    }
}
