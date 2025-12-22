<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\DisplayListUser;

use App\Domain\User\Model\User;

final readonly class DisplayListUserOutput
{
    /**
     * @param list<User> $users
     */
    public function __construct(
        public array $users,
        public int $totalItems,
        public int $totalPages,
    ) {
    }
}
