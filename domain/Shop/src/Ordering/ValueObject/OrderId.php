<?php

namespace App\Domain\Shop\Ordering\ValueObject;

use App\Domain\Shop\Shared\ValueObject\UuidValidationTrait;

final class OrderId
{
    use UuidValidationTrait;

    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        return new self(self::assertUuid($value, 'OrderId'));
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
