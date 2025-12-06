<?php

namespace App\Application\User\Port;

use App\Domain\User\Identity\ValueObject\EmailAddress;

interface TokenProviderInterface
{
    public function generateRandomToken(): string;

    public function encode(string $token, EmailAddress $email): string;

    /**
     * @return array{email: string, token: string}
     */
    public function split(string $encodedToken): array;
}
