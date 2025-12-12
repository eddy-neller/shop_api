<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\ValidateActivation;

use App\Application\Shared\CQRS\Command\CommandInterface;

final readonly class ValidateActivationCommand implements CommandInterface
{
    public function __construct(
        public string $token,
    ) {
    }
}
