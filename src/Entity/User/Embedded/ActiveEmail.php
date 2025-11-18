<?php

namespace App\Entity\User\Embedded;

use DateTime;
use DateTimeInterface;
use Exception;
use JsonSerializable;
use RuntimeException;

final readonly class ActiveEmail implements JsonSerializable
{
    public function __construct(
        public int $mailSent = 0,
        public ?string $token = null,
        public ?int $tokenTtl = null,
        public ?DateTimeInterface $lastAttempt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $lastAttempt = null;
        if (isset($data['lastAttempt'])) {
            try {
                $lastAttempt = $data['lastAttempt'] instanceof DateTimeInterface
                    ? $data['lastAttempt']
                    : new DateTime($data['lastAttempt']);
            } catch (Exception) {
                throw new RuntimeException('Error on lastAttempt');
            }
        }

        return new self(
            mailSent: (int) ($data['mailSent'] ?? 0),
            token: $data['token'] ?? null,
            tokenTtl: $data['tokenTtl'] ?? null,
            lastAttempt: $lastAttempt,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'mailSent' => $this->mailSent,
            'token' => $this->token,
            'tokenTtl' => $this->tokenTtl,
            'lastAttempt' => $this->lastAttempt?->format('c'),
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
            lastAttempt: $this->lastAttempt,
        );
    }

    public function withToken(?string $token): self
    {
        return new self(
            mailSent: $this->mailSent,
            token: $token,
            tokenTtl: $this->tokenTtl,
            lastAttempt: $this->lastAttempt,
        );
    }

    public function withTokenTtl(?int $tokenTtl): self
    {
        return new self(
            mailSent: $this->mailSent,
            token: $this->token,
            tokenTtl: $tokenTtl,
            lastAttempt: $this->lastAttempt,
        );
    }

    public function withLastAttempt(?DateTimeInterface $lastAttempt): self
    {
        return new self(
            mailSent: $this->mailSent,
            token: $this->token,
            tokenTtl: $this->tokenTtl,
            lastAttempt: $lastAttempt,
        );
    }
}
