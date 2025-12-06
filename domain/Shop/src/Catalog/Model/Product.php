<?php

namespace App\Domain\Shop\Catalog\Model;

use App\Domain\Shop\Catalog\ValueObject\CategoryId;
use App\Domain\Shop\Catalog\ValueObject\ProductId;
use App\Domain\Shop\Shared\ValueObject\Money;
use App\Domain\Shop\Shared\ValueObject\Slug;
use DateTimeImmutable;
use InvalidArgumentException;

final class Product
{
    private function __construct(
        private ProductId $id,
        private string $title,
        private string $subtitle,
        private string $description,
        private Money $price,
        private Slug $slug,
        private CategoryId $categoryId,
        private ?string $imageName,
        private ?DateTimeImmutable $imageUpdatedAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        ProductId $id,
        string $title,
        string $subtitle,
        string $description,
        Money $price,
        Slug $slug,
        CategoryId $categoryId,
        DateTimeImmutable $now,
    ): self {
        self::assertTitle($title);
        self::assertSubtitle($subtitle);
        self::assertDescription($description);

        return new self(
            id: $id,
            title: $title,
            subtitle: $subtitle,
            description: $description,
            price: $price,
            slug: $slug,
            categoryId: $categoryId,
            imageName: null,
            imageUpdatedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        ProductId $id,
        string $title,
        string $subtitle,
        string $description,
        Money $price,
        Slug $slug,
        CategoryId $categoryId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?string $imageName = null,
        ?DateTimeImmutable $imageUpdatedAt = null,
    ): self {
        self::assertTitle($title);
        self::assertSubtitle($subtitle);
        self::assertDescription($description);

        return new self(
            id: $id,
            title: $title,
            subtitle: $subtitle,
            description: $description,
            price: $price,
            slug: $slug,
            categoryId: $categoryId,
            imageName: $imageName,
            imageUpdatedAt: $imageUpdatedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function rename(string $title, string $subtitle, DateTimeImmutable $now): void
    {
        self::assertTitle($title);
        self::assertSubtitle($subtitle);

        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->touch($now);
    }

    public function reprice(Money $price, DateTimeImmutable $now): void
    {
        $this->price = $price;
        $this->touch($now);
    }

    public function rewrite(string $description, DateTimeImmutable $now): void
    {
        self::assertDescription($description);

        $this->description = $description;
        $this->touch($now);
    }

    public function moveToCategory(CategoryId $categoryId, DateTimeImmutable $now): void
    {
        $this->categoryId = $categoryId;
        $this->touch($now);
    }

    public function reSlug(Slug $slug, DateTimeImmutable $now): void
    {
        $this->slug = $slug;
        $this->touch($now);
    }

    public function updateImage(?string $imageName, DateTimeImmutable $now): void
    {
        $this->imageName = null === $imageName ? null : trim($imageName);
        $this->imageUpdatedAt = null === $this->imageName ? null : $now;
        $this->touch($now);
    }

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function getCategoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function getImageUpdatedAt(): ?DateTimeImmutable
    {
        return $this->imageUpdatedAt;
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
            throw new InvalidArgumentException('Product title cannot be empty.');
        }
    }

    private static function assertSubtitle(string $subtitle): void
    {
        $trimmed = trim($subtitle);

        if ('' === $trimmed) {
            throw new InvalidArgumentException('Product subtitle cannot be empty.');
        }
    }

    private static function assertDescription(string $description): void
    {
        $trimmed = trim($description);

        if ('' === $trimmed) {
            throw new InvalidArgumentException('Product description cannot be empty.');
        }
    }

    private function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
