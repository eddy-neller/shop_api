<?php

declare(strict_types=1);

namespace App\Domain\User\Tests\Unit\ValueObject;

use App\Domain\User\ValueObject\Avatar;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AvatarTest extends TestCase
{
    public function testConstructWithDefaultValues(): void
    {
        $avatar = new Avatar();

        $this->assertNull($avatar->fileName());
        $this->assertNull($avatar->url());
        $this->assertNull($avatar->updatedAt());
    }

    public function testConstructWithSpecificValues(): void
    {
        $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $avatar = new Avatar(
            fileName: 'avatar.jpg',
            url: 'https://example.com/avatar.jpg',
            updatedAt: $updatedAt,
        );

        $this->assertSame('avatar.jpg', $avatar->fileName());
        $this->assertSame('https://example.com/avatar.jpg', $avatar->url());
        $this->assertSame($updatedAt, $avatar->updatedAt());
    }

    public function testWithFileCreatesNewInstance(): void
    {
        $avatar = new Avatar(fileName: 'old.jpg');
        $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $newAvatar = $avatar->withFile('new.jpg', $updatedAt);

        $this->assertSame('old.jpg', $avatar->fileName());
        $this->assertSame('new.jpg', $newAvatar->fileName());
        $this->assertSame($updatedAt, $newAvatar->updatedAt());
    }

    public function testWithFileIsImmutable(): void
    {
        $avatar = new Avatar(fileName: 'old.jpg');
        $newAvatar = $avatar->withFile('new.jpg');

        $this->assertNotSame($avatar, $newAvatar);
    }

    public function testWithFileUsesCurrentTimeWhenUpdatedAtNotProvided(): void
    {
        $avatar = new Avatar();
        $beforeTime = new DateTimeImmutable();
        $newAvatar = $avatar->withFile('avatar.jpg');
        $afterTime = new DateTimeImmutable();

        $this->assertNotNull($newAvatar->updatedAt());
        $this->assertGreaterThanOrEqual($beforeTime->getTimestamp(), $newAvatar->updatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($afterTime->getTimestamp(), $newAvatar->updatedAt()->getTimestamp());
    }

    public function testWithFileCanSetNull(): void
    {
        $avatar = new Avatar(fileName: 'avatar.jpg');
        $newAvatar = $avatar->withFile(null);

        $this->assertNull($newAvatar->fileName());
    }

    public function testWithFilePreservesUrl(): void
    {
        $avatar = new Avatar(fileName: 'old.jpg', url: 'https://example.com/old.jpg');
        $newAvatar = $avatar->withFile('new.jpg');

        $this->assertSame('https://example.com/old.jpg', $newAvatar->url());
    }

    public function testWithUrlCreatesNewInstance(): void
    {
        $avatar = new Avatar(url: 'https://example.com/old.jpg');
        $newAvatar = $avatar->withUrl('https://example.com/new.jpg');

        $this->assertSame('https://example.com/old.jpg', $avatar->url());
        $this->assertSame('https://example.com/new.jpg', $newAvatar->url());
    }

    public function testWithUrlIsImmutable(): void
    {
        $avatar = new Avatar(url: 'https://example.com/old.jpg');
        $newAvatar = $avatar->withUrl('https://example.com/new.jpg');

        $this->assertNotSame($avatar, $newAvatar);
    }

    public function testWithUrlCanSetNull(): void
    {
        $avatar = new Avatar(url: 'https://example.com/avatar.jpg');
        $newAvatar = $avatar->withUrl(null);

        $this->assertNull($newAvatar->url());
    }

    public function testWithUrlPreservesFileNameAndUpdatedAt(): void
    {
        $updatedAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $avatar = new Avatar(
            fileName: 'avatar.jpg',
            url: 'https://example.com/old.jpg',
            updatedAt: $updatedAt,
        );
        $newAvatar = $avatar->withUrl('https://example.com/new.jpg');

        $this->assertSame('avatar.jpg', $newAvatar->fileName());
        $this->assertSame($updatedAt, $newAvatar->updatedAt());
    }
}
