<?php

declare(strict_types=1);

namespace App\Application\User\Service;

use App\Application\User\Port\UserRepositoryInterface;
use App\Application\User\Port\UserUniquenessCheckerInterface;
use App\Domain\User\Exception\Uniqueness\EmailAlreadyUsedException;
use App\Domain\User\Exception\Uniqueness\UsernameAlreadyUsedException;
use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\Username;

final class UserUniquenessChecker implements UserUniquenessCheckerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {
    }

    public function ensureEmailAndUsernameAvailable(EmailAddress $email, Username $username): void
    {
        $existingByEmail = $this->repository->findByEmail($email);
        if (null !== $existingByEmail) {
            throw new EmailAlreadyUsedException();
        }

        $existingByUsername = $this->repository->findByUsername($username);
        if (null !== $existingByUsername) {
            throw new UsernameAlreadyUsedException();
        }
    }
}
