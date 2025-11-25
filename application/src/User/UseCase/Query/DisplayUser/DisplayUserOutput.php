<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\DisplayUser;

use App\Domain\User\Model\User;

final class DisplayUserOutput
{
    public function __construct(
        public readonly User $user,
    ) {
    }
}
