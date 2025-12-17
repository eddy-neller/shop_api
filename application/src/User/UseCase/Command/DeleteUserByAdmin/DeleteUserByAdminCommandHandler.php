<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\DeleteUserByAdmin;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserNotFoundException;

final readonly class DeleteUserByAdminCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private ClockInterface $clock,
        private TransactionalInterface $transactional,
    ) {
    }

    public function handle(DeleteUserByAdminCommand $command): void
    {
        $user = $this->repository->findById($command->userId);

        if (null === $user) {
            throw new UserNotFoundException('User not found.');
        }

        $this->transactional->transactional(function () use ($user): void {
            $now = $this->clock->now();
            $user->delete($now);

            $this->repository->delete($user);
        });
    }
}
