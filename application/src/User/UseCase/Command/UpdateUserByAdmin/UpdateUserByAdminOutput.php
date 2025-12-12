<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateUserByAdmin;

use App\Domain\User\Model\User;

final readonly class UpdateUserByAdminOutput
{
    public function __construct(
        public User $user,
    ) {
    }
}
