<?php

namespace App\Presentation\User\Dto\Partial;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class UserPreferences
{
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 2, exactMessage: 'The language must be exactly 2 characters long.')]
    #[Groups(groups: ['user:item:write'])]
    public string $lang;
}
