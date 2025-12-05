<?php

namespace App\Domain\User\Exception;

final class EmailAlreadyUsedException extends UserDomainException
{
    public function __construct(string $message = 'Adresse email déjà utilisée.')
    {
        parent::__construct($message);
    }
}
