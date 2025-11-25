<?php

declare(strict_types=1);

namespace App\Domain\User\Tests\Unit\ValueObject;

use App\Domain\User\ValueObject\Username;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UsernameTest extends TestCase
{
    public function testConstructCreatesValidUsername(): void
    {
        $username = new Username('john');

        $this->assertSame('john', $username->toString());
    }

    public function testConstructTrimsWhitespace(): void
    {
        $username = new Username('  john  ');

        $this->assertSame('john', $username->toString());
    }

    public function testConstructThrowsExceptionWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom d\'utilisateur ne peut pas être vide.');

        new Username('');
    }

    public function testConstructThrowsExceptionWhenOnlyWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom d\'utilisateur ne peut pas être vide.');

        new Username('   ');
    }

    public function testConstructThrowsExceptionWhenTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom d\'utilisateur doit contenir au moins 2 caractères.');

        new Username('a');
    }

    public function testConstructThrowsExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom d\'utilisateur ne peut pas dépasser 20 caractères.');

        new Username(str_repeat('a', 21));
    }

    public function testConstructAcceptsMinimumLength(): void
    {
        $username = new Username('ab');

        $this->assertSame('ab', $username->toString());
    }

    public function testConstructAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 20);
        $username = new Username($value);

        $this->assertSame($value, $username->toString());
    }

    public function testConstructHandlesMultibyteCharacters(): void
    {
        $username = new Username('été');

        $this->assertSame('été', $username->toString());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $username1 = new Username('john');
        $username2 = new Username('john');

        $this->assertTrue($username1->equals($username2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $username1 = new Username('john');
        $username2 = new Username('jane');

        $this->assertFalse($username1->equals($username2));
    }

    public function testToStringReturnsValue(): void
    {
        $username = new Username('john');

        $this->assertSame('john', (string) $username);
    }
}
