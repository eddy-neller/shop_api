<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\CheckPasswordResetToken;

use App\Application\Shared\CQRS\Query\QueryInterface;

final class CheckPasswordResetTokenQuery implements QueryInterface
{
    public function __construct(
        public readonly string $token,
    ) {
    }
}
