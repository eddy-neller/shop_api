<?php

namespace App\Domain\Shop\Shared\ValueObject;

use InvalidArgumentException;

trait UuidValidationTrait
{
    private const string UUID_PATTERN = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/';

    protected static function assertUuid(string $value, string $label): string
    {
        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw new InvalidArgumentException(sprintf('%s cannot be empty.', $label));
        }

        if (!preg_match(self::UUID_PATTERN, $trimmed)) {
            throw new InvalidArgumentException(sprintf('%s must be a valid UUID.', $label));
        }

        return $trimmed;
    }
}
