<?php

namespace App\Domain\User\Profile\ValueObject;

use DateTimeImmutable;

final readonly class Avatar
{
    public function __construct(
        private ?string $fileName = null,
        private ?string $url = null,
        private ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function fileName(): ?string
    {
        return $this->fileName;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withFile(?string $fileName, ?DateTimeImmutable $updatedAt = null): self
    {
        return new self(
            fileName: $fileName,
            url: $this->url,
            updatedAt: $updatedAt ?? new DateTimeImmutable(),
        );
    }

    public function withUrl(?string $url): self
    {
        return new self(
            fileName: $this->fileName,
            url: $url,
            updatedAt: $this->updatedAt,
        );
    }
}
