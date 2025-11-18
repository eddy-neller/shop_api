<?php

namespace App\Enum\User;

enum UserTokenScope: string
{
    case RegisterActivation = 'activeEmail';
    case ResetPassword = 'resetPassword';
}
