<?php

namespace App\Domain\User\Exception;

final class UsernameAlreadyUsedException extends UserDomainException
{
    public function __construct(string $message = 'Nom d\'utilisateur déjà utilisé.')
    {
        parent::__construct($message);
    }
}
