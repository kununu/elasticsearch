<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Util;

use ReflectionClass;

trait ConstantContainerTrait
{
    public static function all(bool $preserveKeys = false): array
    {
        $constants = (new ReflectionClass(self::class))->getConstants();

        return $preserveKeys ? $constants : array_values($constants);
    }

    public static function hasConstant(string $constant): bool
    {
        return in_array($constant, self::all());
    }
}
