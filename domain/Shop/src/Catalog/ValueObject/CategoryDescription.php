<?php

declare(strict_types=1);

namespace App\Domain\Shop\Catalog\ValueObject;

use InvalidArgumentException;

final readonly class CategoryDescription
{
    private const int MIN_LENGTH = 2;

    private const int MAX_LENGTH = 1000;

    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $normalized = trim($value);

        if ('' === $normalized) {
            throw new InvalidArgumentException('Category description cannot be empty.');
        }

        $length = self::stringLength($normalized);

        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(sprintf('Category description must be at least %d characters long.', self::MIN_LENGTH));
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('Category description must be at most %d characters long.', self::MAX_LENGTH));
        }

        return new self($normalized);
    }

    public static function fromNullableString(?string $value): ?self
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);

        if ('' === $normalized) {
            return null;
        }

        return self::fromString($normalized);
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

    private static function stringLength(string $value): int
    {
        return mb_strlen($value);
    }
}
