<?php

namespace App\Dto\User;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordResetCheckInput
{
    #[Groups(groups: ['user:item:users-password-reset-check'])]
    #[Assert\NotBlank]
    public string $token;
}
