<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\DeleteUserByAdmin;

use App\Application\Shared\CQRS\Command\CommandInterface;
use App\Domain\User\Identity\ValueObject\UserId;

final class DeleteUserByAdminCommand implements CommandInterface
{
    public function __construct(
        public readonly UserId $userId,
    ) {
    }
}
