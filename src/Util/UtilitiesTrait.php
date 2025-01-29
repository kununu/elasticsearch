<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Util;

trait UtilitiesTrait
{
    protected static function filterNullAndEmptyValues(array $values, bool $recursive = false): array
    {
        if ($recursive) {
            foreach ($values as &$value) {
                if (is_array($value)) {
                    $value = self::filterNullAndEmptyValues($value, true);
                }
            }
        }

        return array_filter($values, fn($value) => $value !== null && $value !== []);
    }

    protected static function formatMultiple(
        string $separator,
        string $itemMask,
        int|float|string ...$values,
    ): string {
        return sprintf(implode($separator, array_fill(0, count($values), $itemMask)), ...$values);
    }
}
