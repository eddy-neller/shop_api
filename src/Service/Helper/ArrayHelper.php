<?php

namespace App\Service\Helper;

abstract class ArrayHelper
{
    public static function arrayKeysExist(mixed $keys, array $array): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
        }

        return true;
    }

    public static function isInMultipleArray(mixed $value, array $array, bool $isKeyAsked = false): bool|int|string
    {
        foreach ($array as $item) {
            if (in_array($value, $item)) {
                if (!$isKeyAsked) {
                    return true;
                }

                $keys = array_keys($array, $item);

                return end($keys);
            }
        }

        return false;
    }
}
