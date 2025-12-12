<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UploadAndUpdateAvatar;

use App\Domain\User\Model\User;

final readonly class UploadAndUpdateAvatarOutput
{
    public function __construct(
        public User $user,
    ) {
    }
}
