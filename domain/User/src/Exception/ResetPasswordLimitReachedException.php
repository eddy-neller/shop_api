<?php

namespace App\Domain\User\Exception;

final class ResetPasswordLimitReachedException extends UserDomainException
{
    public function __construct()
    {
        parent::__construct('Nombre maximal d\'emails de réinitialisation atteint.');
    }
}
