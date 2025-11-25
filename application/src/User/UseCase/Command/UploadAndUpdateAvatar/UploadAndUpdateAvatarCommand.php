<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UploadAndUpdateAvatar;

use App\Application\Shared\CQRS\Command\CommandInterface;
use App\Application\Shared\Port\FileInterface;
use App\Domain\User\ValueObject\UserId;

final class UploadAndUpdateAvatarCommand implements CommandInterface
{
    public function __construct(
        public readonly UserId $userId,
        public readonly FileInterface $avatarFile,
    ) {
    }
}
