<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\CheckPasswordResetToken;

final class CheckPasswordResetTokenOutput
{
    public function __construct(
        public readonly bool $isValid,
    ) {
    }
}
