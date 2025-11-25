<?php

namespace App\Presentation\User\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class UserActivationValidationInput
{
    #[Groups(groups: ['user:item:users-register-validation:write'])]
    #[Assert\NotBlank]
    public string $token;
}
