<?php

namespace App\Application\User\Port;

use App\Domain\User\Model\User;
use App\Domain\User\ValueObject\EmailAddress;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;

interface UserRepositoryInterface
{
    public function nextIdentity(): UserId;

    public function save(User $user): void;

    public function delete(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(EmailAddress $email): ?User;

    public function findByActivationToken(string $token): ?User;

    public function findByResetPasswordToken(string $token): ?User;

    public function findByUsername(Username $username): ?User;

    /**
     * @return User[]
     */
    public function findAll(): array;
}
