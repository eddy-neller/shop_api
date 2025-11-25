<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\RegisterUser;

use App\Application\Shared\CQRS\Command\CommandInterface;

/**
 * @param array{lang?: string}|null $preferences
 */
final class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $username,
        public readonly string $plainPassword,
        public readonly ?array $preferences = null,
    ) {
    }
}
