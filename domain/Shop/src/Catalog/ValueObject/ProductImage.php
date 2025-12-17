<?php

declare(strict_types=1);

namespace App\Domain\Shop\Catalog\ValueObject;

final readonly class ProductImage
{
    public function __construct(
        private ?string $fileName = null,
    ) {
    }

    public function fileName(): ?string
    {
        return $this->fileName;
    }

    public function withFile(?string $fileName): self
    {
        return new self(
            fileName: $fileName,
        );
    }
}
