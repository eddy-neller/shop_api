<?php

namespace App\Domain\User\ValueObject;

use InvalidArgumentException;

final class Firstname
{
    private const int MIN_LENGTH = 2;

    private const int MAX_LENGTH = 50;

    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('Le prénom ne peut pas être vide.');
        }

        $length = mb_strlen($trimmed);
        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(sprintf('Le prénom doit contenir au moins %d caractères.', self::MIN_LENGTH));
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('Le prénom ne peut pas dépasser %d caractères.', self::MAX_LENGTH));
        }

        $this->value = $trimmed;
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
