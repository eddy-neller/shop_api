<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateUserByAdmin;

use App\Domain\User\Model\User;

final class UpdateUserByAdminOutput
{
    public function __construct(
        public readonly User $user,
    ) {
    }
}
