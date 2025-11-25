<?php

namespace App\Domain\User\ValueObject;

use InvalidArgumentException;

final class HashedPassword
{
    public function __construct(
        private readonly string $value,
    ) {
        if ('' === trim($value)) {
            throw new InvalidArgumentException('Le mot de passe haché ne peut pas être vide.');
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}
