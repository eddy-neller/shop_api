<?php

namespace App\Presentation\User\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class UserActivationRequestInput
{
    #[Groups(groups: ['user:item:users-register-resend:write'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
}
