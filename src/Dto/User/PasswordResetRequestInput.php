<?php

namespace App\Dto\User;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordResetRequestInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(groups: ['user:item:users-password-reset-request'])]
    public string $email;
}
