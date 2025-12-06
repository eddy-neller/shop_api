<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\ResetWrongPasswordAttempts;

use App\Application\Shared\Port\ClockInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Identity\ValueObject\UserId;

final readonly class ResetWrongPasswordAttemptsCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function handle(ResetWrongPasswordAttemptsCommand $command): void
    {
        $user = $this->repository->findById(UserId::fromString($command->userId));

        if (null === $user) {
            return;
        }

        $user->resetWrongPasswordAttempts($this->clock->now());

        $this->repository->save($user);
    }
}
