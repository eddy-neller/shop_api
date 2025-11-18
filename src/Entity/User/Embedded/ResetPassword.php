<?php

namespace App\Entity\User\Embedded;

use JsonSerializable;

final readonly class ResetPassword implements JsonSerializable
{
    public function __construct(
        public int $mailSent = 0,
        public ?string $token = null,
        public ?int $tokenTtl = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            mailSent: (int) ($data['mailSent'] ?? 0),
            token: $data['token'] ?? null,
            tokenTtl: $data['tokenTtl'] ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'mailSent' => $this->mailSent,
            'token' => $this->token,
            'tokenTtl' => $this->tokenTtl,
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public function withMailSent(int $mailSent): self
    {
        return new self(
            mailSent: $mailSent,
            token: $this->token,
            tokenTtl: $this->tokenTtl,
        );
    }

    public function withToken(?string $token): self
    {
        return new self(
            mailSent: $this->mailSent,
            token: $token,
            tokenTtl: $this->tokenTtl,
        );
    }

    public function withTokenTtl(?int $tokenTtl): self
    {
        return new self(
            mailSent: $this->mailSent,
            token: $this->token,
            tokenTtl: $tokenTtl,
        );
    }
}
