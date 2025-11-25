<?php

namespace App\Domain\User\Exception;

final class UserLockedException extends UserDomainException
{
    public function __construct()
    {
        parent::__construct('Le compte est verrouillé.');
    }
}
