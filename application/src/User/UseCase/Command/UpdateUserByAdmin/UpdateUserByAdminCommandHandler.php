<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateUserByAdmin;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\PasswordHasherInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\Firstname;
use App\Domain\User\Identity\ValueObject\Lastname;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Security\ValueObject\RoleSet;
use App\Domain\User\Security\ValueObject\UserStatus;

final readonly class UpdateUserByAdminCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private PasswordHasherInterface $passwordHasher,
        private ClockInterface $clock,
        private TransactionalInterface $transactional,
    ) {
    }

    public function handle(UpdateUserByAdminCommand $command): UpdateUserByAdminOutput
    {
        $user = $this->repository->findById($command->userId);

        if (null === $user) {
            throw new UserNotFoundException('User not found.');
        }

        return $this->transactional->transactional(function () use ($user, $command): UpdateUserByAdminOutput {
            $now = $this->clock->now();
            $hashedPassword = null;
            if (null !== $command->plainPassword && '' !== trim($command->plainPassword)) {
                $hashedPassword = $this->passwordHasher->hash($command->plainPassword);
            }

            $user->updateByAdmin(
                now: $now,
                username: $command->username ? new Username($command->username) : null,
                email: $command->email ? new EmailAddress($command->email) : null,
                firstname: $command->firstname ? new Firstname($command->firstname) : null,
                lastname: $command->lastname ? new Lastname($command->lastname) : null,
                roles: $command->roles ? new RoleSet($command->roles) : null,
                status: $command->status ? UserStatus::fromInt($command->status) : null,
                password: $hashedPassword,
            );

            $this->repository->save($user);

            return new UpdateUserByAdminOutput($user);
        });
    }
}
