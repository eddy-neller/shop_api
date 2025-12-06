<?php

namespace App\Domain\Shop\Customer\Model;

use App\Domain\Shop\Customer\ValueObject\CustomerId;
use App\Domain\Shop\Customer\ValueObject\UserAccountId;
use DateTimeImmutable;

final class Customer
{
    private function __construct(
        private CustomerId $id,
        private ?UserAccountId $userAccountId,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function register(
        CustomerId $id,
        DateTimeImmutable $now,
        ?UserAccountId $userAccountId = null,
    ): self {
        return new self(
            id: $id,
            userAccountId: $userAccountId,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        CustomerId $id,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?UserAccountId $userAccountId = null,
    ): self {
        return new self(
            id: $id,
            userAccountId: $userAccountId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function linkToAccount(UserAccountId $userAccountId, DateTimeImmutable $now): void
    {
        $this->userAccountId = $userAccountId;
        $this->touch($now);
    }

    public function unlinkAccount(DateTimeImmutable $now): void
    {
        $this->userAccountId = null;
        $this->touch($now);
    }

    public function getId(): CustomerId
    {
        return $this->id;
    }

    public function getUserAccountId(): ?UserAccountId
    {
        return $this->userAccountId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
