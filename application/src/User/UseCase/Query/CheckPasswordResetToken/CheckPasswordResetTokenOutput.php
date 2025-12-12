<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\CheckPasswordResetToken;

final readonly class CheckPasswordResetTokenOutput
{
    public function __construct(
        public bool $isValid,
    ) {
    }
}
