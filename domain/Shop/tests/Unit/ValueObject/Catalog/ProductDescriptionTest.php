<?php

declare(strict_types=1);

namespace App\Domain\Shop\Tests\Unit\ValueObject\Catalog;

use App\Domain\Shop\Catalog\ValueObject\ProductDescription;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProductDescriptionTest extends TestCase
{
    public function testFromStringTrimsWhitespace(): void
    {
        $description = ProductDescription::fromString('  Soft cotton knit.  ');

        $this->assertSame('Soft cotton knit.', $description->toString());
    }

    public function testFromStringThrowsWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product description cannot be empty.');

        ProductDescription::fromString('   ');
    }

    public function testFromStringThrowsWhenTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product description must be at least 2 characters long.');

        ProductDescription::fromString('a');
    }

    public function testFromStringThrowsWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product description must be at most 1000 characters long.');

        ProductDescription::fromString(str_repeat('a', 1001));
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $description1 = ProductDescription::fromString('Soft cotton knit.');
        $description2 = ProductDescription::fromString('Soft cotton knit.');

        $this->assertTrue($description1->equals($description2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $description1 = ProductDescription::fromString('Soft cotton knit.');
        $description2 = ProductDescription::fromString('Warm wool knit.');

        $this->assertFalse($description1->equals($description2));
    }

    public function testToStringCastsToString(): void
    {
        $description = ProductDescription::fromString('Soft cotton knit.');

        $this->assertSame('Soft cotton knit.', (string) $description);
    }
}
