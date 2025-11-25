<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\CreateUserByAdmin;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\PasswordHasherInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Model\User;
use App\Domain\User\ValueObject\EmailAddress;
use App\Domain\User\ValueObject\Firstname;
use App\Domain\User\ValueObject\Lastname;
use App\Domain\User\ValueObject\Preferences;
use App\Domain\User\ValueObject\RoleSet;
use App\Domain\User\ValueObject\Username;
use App\Domain\User\ValueObject\UserStatus;

final class CreateUserByAdminHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ClockInterface $clock,
        private readonly TransactionalInterface $transactional,
    ) {
    }

    public function handle(CreateUserByAdminCommand $command): CreateUserByAdminOutput
    {
        return $this->transactional->transactional(function () use ($command): CreateUserByAdminOutput {
            $now = $this->clock->now();
            $userId = $this->repository->nextIdentity();
            $email = new EmailAddress($command->email);
            $hashedPassword = $this->passwordHasher->hash($command->plainPassword);
            $roles = new RoleSet($command->roles);
            $status = UserStatus::fromInt($command->status);

            $user = User::createByAdmin(
                id: $userId,
                username: new Username($command->username),
                email: $email,
                password: $hashedPassword,
                roles: $roles,
                status: $status,
                now: $now,
                firstname: $command->firstname ? new Firstname($command->firstname) : null,
                lastname: $command->lastname ? new Lastname($command->lastname) : null,
                preferences: new Preferences(),
            );

            $this->repository->save($user);

            return new CreateUserByAdminOutput($user);
        });
    }
}
