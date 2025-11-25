<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateAvatar;

use App\Domain\User\Model\User;

final class UpdateAvatarOutput
{
    public function __construct(
        public readonly User $user,
    ) {
    }
}
