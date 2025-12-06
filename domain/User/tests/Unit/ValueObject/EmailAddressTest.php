<?php

declare(strict_types=1);

namespace App\Domain\User\Tests\Unit\ValueObject;

use App\Domain\User\Identity\ValueObject\EmailAddress;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailAddressTest extends TestCase
{
    public function testConstructCreatesValidEmailAddress(): void
    {
        $email = new EmailAddress('john@example.com');

        $this->assertSame('john@example.com', $email->toString());
    }

    public function testConstructNormalizesToLowercase(): void
    {
        $email = new EmailAddress('John@Example.COM');

        $this->assertSame('john@example.com', $email->toString());
    }

    public function testConstructTrimsWhitespace(): void
    {
        $email = new EmailAddress('  john@example.com  ');

        $this->assertSame('john@example.com', $email->toString());
    }

    public function testConstructThrowsExceptionForInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Adresse email invalide.');

        new EmailAddress('invalid-email');
    }

    public function testConstructThrowsExceptionForEmptyEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Adresse email invalide.');

        new EmailAddress('');
    }

    public function testConstructThrowsExceptionForEmailWithoutAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Adresse email invalide.');

        new EmailAddress('johndomain.com');
    }

    public function testConstructThrowsExceptionForEmailWithoutDomain(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Adresse email invalide.');

        new EmailAddress('john@');
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $email1 = new EmailAddress('john@example.com');
        $email2 = new EmailAddress('john@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function testEqualsReturnsTrueForCaseInsensitiveMatch(): void
    {
        $email1 = new EmailAddress('John@Example.com');
        $email2 = new EmailAddress('john@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $email1 = new EmailAddress('john@example.com');
        $email2 = new EmailAddress('jane@example.com');

        $this->assertFalse($email1->equals($email2));
    }

    public function testToStringReturnsValue(): void
    {
        $email = new EmailAddress('john@example.com');

        $this->assertSame('john@example.com', (string) $email);
    }
}
