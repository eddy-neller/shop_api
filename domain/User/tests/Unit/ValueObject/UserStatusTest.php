<?php

declare(strict_types=1);

namespace App\Domain\User\Tests\Unit\ValueObject;

use App\Domain\User\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;

final class UserStatusTest extends TestCase
{
    public function testConstructWithDefaultValue(): void
    {
        $status = new UserStatus();

        $this->assertSame(UserStatus::INACTIVE, $status->toInt());
    }

    public function testConstructWithSpecificValue(): void
    {
        $status = new UserStatus(UserStatus::ACTIVE);

        $this->assertSame(UserStatus::ACTIVE, $status->toInt());
    }

    public function testInactiveCreatesInactiveStatus(): void
    {
        $status = UserStatus::inactive();

        $this->assertSame(UserStatus::INACTIVE, $status->toInt());
        $this->assertFalse($status->hasFlag(UserStatus::ACTIVE));
    }

    public function testFromIntCreatesStatusWithValue(): void
    {
        $status = UserStatus::fromInt(UserStatus::BLOCKED);

        $this->assertSame(UserStatus::BLOCKED, $status->toInt());
    }

    public function testAddFlagAddsFlag(): void
    {
        $status = UserStatus::inactive();
        $newStatus = $status->addFlag(UserStatus::ACTIVE);

        $this->assertSame(UserStatus::INACTIVE, $status->toInt());
        $this->assertTrue($newStatus->hasFlag(UserStatus::ACTIVE));
    }

    public function testAddFlagIsImmutable(): void
    {
        $status = UserStatus::inactive();
        $newStatus = $status->addFlag(UserStatus::ACTIVE);

        $this->assertNotSame($status, $newStatus);
        $this->assertSame(UserStatus::INACTIVE, $status->toInt());
    }

    public function testRemoveFlagRemovesFlag(): void
    {
        $status = UserStatus::fromInt(UserStatus::ACTIVE | UserStatus::BLOCKED);
        $newStatus = $status->removeFlag(UserStatus::BLOCKED);

        $this->assertTrue($newStatus->hasFlag(UserStatus::ACTIVE));
        $this->assertFalse($newStatus->hasFlag(UserStatus::BLOCKED));
    }

    public function testRemoveFlagIsImmutable(): void
    {
        $status = UserStatus::fromInt(UserStatus::ACTIVE | UserStatus::BLOCKED);
        $newStatus = $status->removeFlag(UserStatus::BLOCKED);

        $this->assertNotSame($status, $newStatus);
        $this->assertTrue($status->hasFlag(UserStatus::BLOCKED));
    }

    public function testHasFlagReturnsTrueWhenFlagIsSet(): void
    {
        $status = UserStatus::fromInt(UserStatus::ACTIVE);

        $this->assertTrue($status->hasFlag(UserStatus::ACTIVE));
    }

    public function testHasFlagReturnsFalseWhenFlagIsNotSet(): void
    {
        $status = UserStatus::inactive();

        $this->assertFalse($status->hasFlag(UserStatus::ACTIVE));
    }

    public function testCanCombineMultipleFlags(): void
    {
        $status = UserStatus::inactive()
            ->addFlag(UserStatus::ACTIVE)
            ->addFlag(UserStatus::BLOCKED);

        $this->assertTrue($status->hasFlag(UserStatus::ACTIVE));
        $this->assertTrue($status->hasFlag(UserStatus::BLOCKED));
    }

    public function testToIntReturnsIntValue(): void
    {
        $status = UserStatus::fromInt(UserStatus::ACTIVE);

        $this->assertSame(UserStatus::ACTIVE, $status->toInt());
    }
}
