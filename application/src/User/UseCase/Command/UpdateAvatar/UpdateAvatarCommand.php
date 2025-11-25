<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateAvatar;

use App\Application\Shared\CQRS\Command\CommandInterface;
use App\Domain\User\ValueObject\UserId;

final class UpdateAvatarCommand implements CommandInterface
{
    public function __construct(
        public readonly UserId $userId,
        public readonly ?string $avatarFileName,
        public readonly ?string $avatarUrl = null,
    ) {
    }
}
