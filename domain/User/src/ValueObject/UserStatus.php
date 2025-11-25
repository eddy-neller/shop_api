<?php

namespace App\Domain\User\ValueObject;

final class UserStatus
{
    public const int INACTIVE = 0;

    public const int ACTIVE = 1;

    public const int BLOCKED = 2;

    public function __construct(
        private readonly int $value = self::INACTIVE,
    ) {
    }

    public static function inactive(): self
    {
        return new self(self::INACTIVE);
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function addFlag(int $flag): self
    {
        return new self($this->value | $flag);
    }

    public function removeFlag(int $flag): self
    {
        return new self($this->value & ~$flag);
    }

    public function hasFlag(int $flag): bool
    {
        return ($this->value & $flag) === $flag;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
