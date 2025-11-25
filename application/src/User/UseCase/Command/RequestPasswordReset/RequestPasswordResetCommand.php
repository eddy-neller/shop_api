<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\RequestPasswordReset;

use App\Application\Shared\CQRS\Command\CommandInterface;

final class RequestPasswordResetCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
    ) {
    }
}
