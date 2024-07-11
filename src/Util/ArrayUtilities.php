<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Util;

final class ArrayUtilities
{
    public static function filterNullAndEmptyValues(array $values, bool $recursive = false): array
    {
        if ($recursive) {
            foreach ($values as &$value) {
                if (is_array($value)) {
                    $value = self::filterNullAndEmptyValues($value, true);
                }
            }
        }

        return array_filter($values, fn ($value) => $value !== null && $value !== []);
    }
}
