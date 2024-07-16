<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Util\ArrayUtilities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArrayUtilitiesTest extends TestCase
{
    #[DataProvider('filterNullAndEmptyValuesDataProvider')]
    public function testFilterNullAndEmptyValues(array $data, bool $recursive, array $expected): void
    {
        self::assertEquals($expected, ArrayUtilities::filterNullAndEmptyValues($data, $recursive));
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
                                'l' => ''
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
                                'l' => ''
                            ],
                        ],
                    ],
                    'j' => '',
                ],
            ],
            'recursive' => [
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
                                'l' => ''
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
                                'l' => ''
                            ]
                        ],
                    ],
                ],
            ],
        ];
    }
}
