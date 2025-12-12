<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\CreateUserByAdmin;

use App\Domain\User\Model\User;

final readonly class CreateUserByAdminOutput
{
    public function __construct(
        public User $user,
    ) {
    }
}
