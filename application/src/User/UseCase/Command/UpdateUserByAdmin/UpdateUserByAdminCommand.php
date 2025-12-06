<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateUserByAdmin;

use App\Application\Shared\CQRS\Command\CommandInterface;
use App\Domain\User\Identity\ValueObject\UserId;

final class UpdateUserByAdminCommand implements CommandInterface
{
    /**
     * @param string[]|null $roles
     */
    public function __construct(
        public readonly UserId $userId,
        public readonly ?string $email = null,
        public readonly ?string $username = null,
        public readonly ?string $plainPassword = null,
        public readonly ?array $roles = null,
        public readonly ?int $status = null,
        public readonly ?string $firstname = null,
        public readonly ?string $lastname = null,
    ) {
    }
}
