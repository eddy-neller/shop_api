<?php

namespace App\Domain\Shop\Shipping\Model;

use App\Domain\Shop\Shared\ValueObject\Money;
use App\Domain\Shop\Shipping\ValueObject\CarrierId;
use DateTimeImmutable;
use InvalidArgumentException;

final class Carrier
{
    private function __construct(
        private CarrierId $id,
        private string $name,
        private string $description,
        private Money $price,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        CarrierId $id,
        string $name,
        string $description,
        Money $price,
        DateTimeImmutable $now,
    ): self {
        self::assertName($name);
        self::assertDescription($description);

        return new self(
            id: $id,
            name: $name,
            description: $description,
            price: $price,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        CarrierId $id,
        string $name,
        string $description,
        Money $price,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        self::assertName($name);
        self::assertDescription($description);

        return new self(
            id: $id,
            name: $name,
            description: $description,
            price: $price,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function rename(string $name, DateTimeImmutable $now): void
    {
        self::assertName($name);

        $this->name = $name;
        $this->touch($now);
    }

    public function rewriteDescription(string $description, DateTimeImmutable $now): void
    {
        self::assertDescription($description);

        $this->description = $description;
        $this->touch($now);
    }

    public function updatePrice(Money $price, DateTimeImmutable $now): void
    {
        $this->price = $price;
        $this->touch($now);
    }

    public function getId(): CarrierId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function assertName(string $name): void
    {
        $trimmed = trim($name);

        if ('' === $trimmed) {
            throw new InvalidArgumentException('Carrier name cannot be empty.');
        }
    }

    private static function assertDescription(string $description): void
    {
        $trimmed = trim($description);

        if ('' === $trimmed) {
            throw new InvalidArgumentException('Carrier description cannot be empty.');
        }
    }

    private function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
