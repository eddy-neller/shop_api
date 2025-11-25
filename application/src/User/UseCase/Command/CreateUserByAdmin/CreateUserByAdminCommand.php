<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\CreateUserByAdmin;

use App\Application\Shared\CQRS\Command\CommandInterface;

final class CreateUserByAdminCommand implements CommandInterface
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public readonly string $email,
        public readonly string $username,
        public readonly string $plainPassword,
        public readonly array $roles,
        public readonly int $status,
        public readonly ?string $firstname = null,
        public readonly ?string $lastname = null,
    ) {
    }
}
