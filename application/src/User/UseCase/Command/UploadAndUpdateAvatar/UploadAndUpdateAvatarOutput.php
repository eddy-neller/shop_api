<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UploadAndUpdateAvatar;

use App\Domain\User\Model\User;

final class UploadAndUpdateAvatarOutput
{
    public function __construct(
        public readonly User $user,
    ) {
    }
}
