<?php

declare(strict_types=1);

namespace App\Domain\Shop\Tests\Unit\ValueObject\Catalog;

use App\Domain\Shop\Catalog\ValueObject\CategoryId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CategoryIdTest extends TestCase
{
    private const string UUID = '123e4567-e89b-12d3-a456-426614174000';

    public function testFromStringCreatesValidCategoryId(): void
    {
        $id = CategoryId::fromString(self::UUID);

        $this->assertSame(self::UUID, $id->toString());
    }

    public function testFromStringThrowsWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CategoryId cannot be empty.');

        CategoryId::fromString('');
    }

    public function testFromStringThrowsWhenInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CategoryId must be a valid UUID.');

        CategoryId::fromString('not-a-uuid');
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $id1 = CategoryId::fromString(self::UUID);
        $id2 = CategoryId::fromString(self::UUID);

        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $id1 = CategoryId::fromString(self::UUID);
        $id2 = CategoryId::fromString('123e4567-e89b-12d3-a456-426614174001');

        $this->assertFalse($id1->equals($id2));
    }

    public function testToStringCastsToString(): void
    {
        $id = CategoryId::fromString(self::UUID);

        $this->assertSame(self::UUID, (string) $id);
    }
}
