<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\RegisterUser;

use App\Domain\User\Model\User;

final readonly class RegisterUserOutput
{
    public function __construct(
        public User $user,
    ) {
    }
}
