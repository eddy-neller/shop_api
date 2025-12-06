<?php

declare(strict_types=1);

namespace App\Application\User\Port;

use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\Username;

interface UserUniquenessCheckerInterface
{
    public function ensureEmailAndUsernameAvailable(EmailAddress $email, Username $username): void;
}
