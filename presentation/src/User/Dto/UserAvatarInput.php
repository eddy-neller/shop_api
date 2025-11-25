<?php

namespace App\Presentation\User\Dto;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
class UserAvatarInput
{
    #[Groups(['user:write'])]
    #[Assert\NotNull(message: 'Please upload an avatar.')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/png', 'image/gif', 'image/jpeg', 'image/pjpeg']
    )]
    #[Assert\Image(
        maxWidth: 512,
        maxHeight: 512
    )]
    public ?File $avatarFile = null;
}
