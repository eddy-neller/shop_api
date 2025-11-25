<?php

declare(strict_types=1);

namespace App\Domain\User\Tests\Unit\ValueObject;

use App\Domain\User\ValueObject\Lastname;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LastnameTest extends TestCase
{
    public function testConstructCreatesValidLastname(): void
    {
        $lastname = new Lastname('Doe');

        $this->assertSame('Doe', $lastname->toString());
    }

    public function testConstructTrimsWhitespace(): void
    {
        $lastname = new Lastname('  Doe  ');

        $this->assertSame('Doe', $lastname->toString());
    }

    public function testConstructThrowsExceptionWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom ne peut pas être vide.');

        new Lastname('');
    }

    public function testConstructThrowsExceptionWhenOnlyWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom ne peut pas être vide.');

        new Lastname('   ');
    }

    public function testConstructThrowsExceptionWhenTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom doit contenir au moins 2 caractères.');

        new Lastname('A');
    }

    public function testConstructThrowsExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom ne peut pas dépasser 50 caractères.');

        new Lastname(str_repeat('a', 51));
    }

    public function testConstructAcceptsMinimumLength(): void
    {
        $lastname = new Lastname('Do');

        $this->assertSame('Do', $lastname->toString());
    }

    public function testConstructAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 50);
        $lastname = new Lastname($value);

        $this->assertSame($value, $lastname->toString());
    }

    public function testConstructHandlesMultibyteCharacters(): void
    {
        $lastname = new Lastname('Müller');

        $this->assertSame('Müller', $lastname->toString());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $lastname1 = new Lastname('Doe');
        $lastname2 = new Lastname('Doe');

        $this->assertTrue($lastname1->equals($lastname2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $lastname1 = new Lastname('Doe');
        $lastname2 = new Lastname('Smith');

        $this->assertFalse($lastname1->equals($lastname2));
    }

    public function testToStringReturnsValue(): void
    {
        $lastname = new Lastname('Doe');

        $this->assertSame('Doe', (string) $lastname);
    }
}
