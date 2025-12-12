<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\CheckPasswordResetToken;

use App\Application\Shared\CQRS\Query\QueryInterface;

final readonly class CheckPasswordResetTokenQuery implements QueryInterface
{
    public function __construct(
        public string $token,
    ) {
    }
}
