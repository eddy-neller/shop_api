<?php

namespace App\Service;

class BitField
{
    private static mixed $value;

    public function __construct(int $value = 0)
    {
        self::$value = $value;
    }

    public function getValue(): int
    {
        return self::$value;
    }

    public static function check(int $n): bool
    {
        return (self::$value & $n) === $n;
    }

    public static function set(int $n): void
    {
        self::$value |= $n;
    }

    public static function clear(int $n): void
    {
        self::$value &= ~$n;
    }

    public function addValue(int $old, int $new): int
    {
        self::$value = $old;

        return self::$value += $new;
    }

    public function removeValue(int $old, int $new): int
    {
        self::$value = $old;

        return self::$value -= $new;
    }

    public function checkValue(int $old, int $new): bool
    {
        self::$value = $old;

        return (self::$value & $new) === $new;
    }
}
