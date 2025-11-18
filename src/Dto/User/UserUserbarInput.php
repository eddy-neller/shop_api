<?php

namespace App\Dto\User;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
class UserUserbarInput
{
    #[Groups(['user:write'])]
    #[Assert\NotNull(message: 'Please upload an userbar.')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/png', 'image/gif', 'image/jpeg', 'image/pjpeg']
    )]
    #[Assert\Image(
        maxWidth: 800,
        maxHeight: 200
    )]
    public ?File $userbarFile = null;
}
