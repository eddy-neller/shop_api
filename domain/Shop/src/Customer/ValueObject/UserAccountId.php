<?php

namespace App\Domain\Shop\Customer\ValueObject;

use App\Domain\Shop\Shared\ValueObject\UuidValidationTrait;

/**
 * Represents the identity of an account in the User bounded context.
 */
final class UserAccountId
{
    use UuidValidationTrait;

    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        return new self(self::assertUuid($value, 'UserAccountId'));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
