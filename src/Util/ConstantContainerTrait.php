<?php

namespace Kununu\Elasticsearch\Util;

trait ConstantContainerTrait
{
    public static function all(bool $preserveKeys = false): array
    {
        $constants = (new \ReflectionClass(__CLASS__))->getConstants();

        return $preserveKeys ? $constants : array_values($constants);
    }
    
    public static function hasConstant(string $constant): bool
    {
        return in_array($constant, self::all());
    }
}
