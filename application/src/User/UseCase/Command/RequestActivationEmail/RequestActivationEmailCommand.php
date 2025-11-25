<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\RequestActivationEmail;

use App\Application\Shared\CQRS\Command\CommandInterface;

final class RequestActivationEmailCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
    ) {
    }
}
