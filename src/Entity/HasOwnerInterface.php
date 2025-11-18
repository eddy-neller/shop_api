<?php

namespace App\Entity;

use App\Entity\User\User;

interface HasOwnerInterface
{
    public function setUser(User $user): self;

    public function getUser(): User;
}
