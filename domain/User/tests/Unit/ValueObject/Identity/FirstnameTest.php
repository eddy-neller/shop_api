<?php

declare(strict_types=1);

namespace App\Domain\User\Tests\Unit\ValueObject\Identity;

use App\Domain\User\Identity\ValueObject\Firstname;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FirstnameTest extends TestCase
{
    public function testConstructCreatesValidFirstname(): void
    {
        $firstname = new Firstname('John');

        $this->assertSame('John', $firstname->toString());
    }

    public function testConstructTrimsWhitespace(): void
    {
        $firstname = new Firstname('  John  ');

        $this->assertSame('John', $firstname->toString());
    }

    public function testConstructThrowsExceptionWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom ne peut pas être vide.');

        new Firstname('');
    }

    public function testConstructThrowsExceptionWhenOnlyWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom ne peut pas être vide.');

        new Firstname('   ');
    }

    public function testConstructThrowsExceptionWhenTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom doit contenir au moins 2 caractères.');

        new Firstname('A');
    }

    public function testConstructThrowsExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom ne peut pas dépasser 50 caractères.');

        new Firstname(str_repeat('a', 51));
    }

    public function testConstructAcceptsMinimumLength(): void
    {
        $firstname = new Firstname('Jo');

        $this->assertSame('Jo', $firstname->toString());
    }

    public function testConstructAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 50);
        $firstname = new Firstname($value);

        $this->assertSame($value, $firstname->toString());
    }

    public function testConstructHandlesMultibyteCharacters(): void
    {
        $firstname = new Firstname('François');

        $this->assertSame('François', $firstname->toString());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $firstname1 = new Firstname('John');
        $firstname2 = new Firstname('John');

        $this->assertTrue($firstname1->equals($firstname2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $firstname1 = new Firstname('John');
        $firstname2 = new Firstname('Jane');

        $this->assertFalse($firstname1->equals($firstname2));
    }

    public function testToStringReturnsValue(): void
    {
        $firstname = new Firstname('John');

        $this->assertSame('John', (string) $firstname);
    }
}
