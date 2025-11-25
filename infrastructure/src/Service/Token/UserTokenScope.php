<?php

namespace App\Infrastructure\Service\Token;

enum UserTokenScope: string
{
    case RegisterActivation = 'registerActivation';
    case ResetPassword = 'resetPassword';
}
