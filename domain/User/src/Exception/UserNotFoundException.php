<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use Throwable;

final class UserNotFoundException extends UserDomainException
{
    public function __construct(
        string $message = 'User not found.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
