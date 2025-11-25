<?php

namespace App\Application\User\Port;

use App\Domain\User\ValueObject\HashedPassword;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): HashedPassword;
}
