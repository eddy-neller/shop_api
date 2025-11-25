<?php

namespace App\Infrastructure\Service\Token;

use App\Application\User\Port\TokenProviderInterface;
use App\Domain\User\ValueObject\EmailAddress;
use App\Infrastructure\Service\Encoder\CustomEncoder;
use App\Infrastructure\Service\User\TokenManager;

final class TokenProvider implements TokenProviderInterface
{
    public function __construct(
        private readonly TokenManager $tokenManager,
    ) {
    }

    public function generateRandomToken(): string
    {
        return CustomEncoder::randomString();
    }

    public function encode(string $token, EmailAddress $email): string
    {
        return $this->tokenManager->generateEmailToken($token, $email->toString());
    }

    public function split(string $encodedToken): array
    {
        return $this->tokenManager->splitToken($encodedToken);
    }
}
