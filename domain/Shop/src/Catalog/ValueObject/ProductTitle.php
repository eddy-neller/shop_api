<?php

declare(strict_types=1);

namespace App\Domain\Shop\Catalog\ValueObject;

use InvalidArgumentException;

final readonly class ProductTitle
{
    private const int MIN_LENGTH = 2;

    private const int MAX_LENGTH = 100;

    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $normalized = trim($value);

        if ('' === $normalized) {
            throw new InvalidArgumentException('Product title cannot be empty.');
        }

        $length = self::stringLength($normalized);

        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(sprintf('Product title must be at least %d characters long.', self::MIN_LENGTH));
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('Product title must be at most %d characters long.', self::MAX_LENGTH));
        }

        return new self($normalized);
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
