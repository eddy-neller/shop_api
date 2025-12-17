<?php

namespace App\Application\User\Port;

use App\Application\Shared\Port\FileInterface;
use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Model\User;

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

    public function updateAvatar(UserId $id, FileInterface $file): ?User;
}
