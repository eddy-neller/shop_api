<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\ConfirmPasswordReset;

use App\Application\Shared\CQRS\Command\CommandInterface;

final class ConfirmPasswordResetCommand implements CommandInterface
{
    public function __construct(
        public readonly string $token,
        public readonly string $newPassword,
    ) {
    }
}
