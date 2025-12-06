<?php

namespace App\Domain\Shop\Catalog\Model;

use App\Domain\Shop\Catalog\ValueObject\CategoryId;
use App\Domain\Shop\Shared\ValueObject\Slug;
use DateTimeImmutable;
use InvalidArgumentException;

final class Category
{
    private function __construct(
        private CategoryId $id,
        private string $title,
        private ?string $description,
        private Slug $slug,
        private ?CategoryId $parentId,
        private int $productCount,
        private int $level,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        CategoryId $id,
        string $title,
        Slug $slug,
        DateTimeImmutable $now,
        ?CategoryId $parentId = null,
        ?string $description = null,
        int $level = 0,
    ): self {
        self::assertTitle($title);
        self::assertLevel($level);

        return new self(
            id: $id,
            title: $title,
            description: $description,
            slug: $slug,
            parentId: $parentId,
            productCount: 0,
            level: $level,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        CategoryId $id,
        string $title,
        Slug $slug,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?CategoryId $parentId = null,
        ?string $description = null,
        int $productCount = 0,
        int $level = 0,
    ): self {
        self::assertTitle($title);
        self::assertLevel($level);

        if ($productCount < 0) {
            throw new InvalidArgumentException('Product count cannot be negative.');
        }

        return new self(
            id: $id,
            title: $title,
            description: $description,
            slug: $slug,
            parentId: $parentId,
            productCount: $productCount,
            level: $level,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function rename(string $title, DateTimeImmutable $now): void
    {
        self::assertTitle($title);
        $this->title = $title;
        $this->touch($now);
    }

    public function describe(?string $description, DateTimeImmutable $now): void
    {
        $this->description = null === $description ? null : trim($description);
        $this->touch($now);
    }

    public function moveTo(?CategoryId $parentId, int $level, DateTimeImmutable $now): void
    {
        self::assertLevel($level);
        $this->parentId = $parentId;
        $this->level = $level;
        $this->touch($now);
    }

    public function increaseProductCount(DateTimeImmutable $now): void
    {
        ++$this->productCount;
        $this->touch($now);
    }

    public function decreaseProductCount(DateTimeImmutable $now): void
    {
        if (0 === $this->productCount) {
            throw new InvalidArgumentException('Product count cannot be negative.');
        }

        --$this->productCount;
        $this->touch($now);
    }

    public function getId(): CategoryId
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function getParentId(): ?CategoryId
    {
        return $this->parentId;
    }

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function assertTitle(string $title): void
    {
        $trimmed = trim($title);

        if ('' === $trimmed) {
            throw new InvalidArgumentException('Category title cannot be empty.');
        }
    }

    private static function assertLevel(int $level): void
    {
        if ($level < 0) {
            throw new InvalidArgumentException('Category level must be positive.');
        }
    }

    private function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
