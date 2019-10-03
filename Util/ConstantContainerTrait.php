<?php

namespace App\Services\Elasticsearch\Util;

trait ConstantContainerTrait
{
    /**
     * @param bool $preserveKeys
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function all($preserveKeys = false): array
    {
        $constants = (new \ReflectionClass(__CLASS__))->getConstants();

        return $preserveKeys ? $constants : array_values($constants);
    }

    /**
     * @param string $constant
     *
     * @return bool
     * @throws \ReflectionException
     */
    public static function hasConstant(string $constant): bool
    {
        return in_array($constant, self::all());
    }
}
