<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\DisplayUser;

use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserNotFoundException;

final readonly class DisplayUserQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {
    }

    public function handle(DisplayUserQuery $query): DisplayUserOutput
    {
        $user = $this->repository->findById($query->userId);

        if (null === $user) {
            throw new UserNotFoundException('User not found.');
        }

        return new DisplayUserOutput($user);
    }
}
