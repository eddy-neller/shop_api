<?php

namespace App\Enum\User;

enum UserStatus: string
{
    case INACTIVE = 'inactive';
    case ACTIVE = 'active';
    case BLOCKED = 'bloked';

    public const array VALUES = [
        self::INACTIVE->value,
        self::ACTIVE->value,
        self::BLOCKED->value,
    ];

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }

    public function isInactive(): bool
    {
        return self::INACTIVE === $this;
    }

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }

    public function isBloked(): bool
    {
        return self::BLOCKED === $this;
    }
}
