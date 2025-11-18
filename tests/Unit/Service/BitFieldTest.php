<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\BitField;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BitFieldTest extends KernelTestCase
{
    private BitField $bitField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bitField = new BitField(0);
    }

    public function testConstructorWithDefaultValue(): void
    {
        $bitField = new BitField();
        $this->assertSame(0, $bitField->getValue());
    }

    public function testConstructorWithCustomValue(): void
    {
        $bitField = new BitField(5);
        $this->assertSame(5, $bitField->getValue());
    }

    public function testGetValue(): void
    {
        $bitField = new BitField(10);
        $this->assertSame(10, $bitField->getValue());
    }

    public function testCheckWithMatchingBits(): void
    {
        new BitField(7); // 111 in binary
        $this->assertTrue(BitField::check(4)); // 100 in binary
        $this->assertTrue(BitField::check(2)); // 010 in binary
        $this->assertTrue(BitField::check(1)); // 001 in binary
    }

    public function testCheckWithNonMatchingBits(): void
    {
        new BitField(5); // 101 in binary
        $this->assertFalse(BitField::check(2)); // 010 in binary
        $this->assertFalse(BitField::check(6)); // 110 in binary
    }

    public function testSet(): void
    {
        $bitField = new BitField(1); // 001 in binary
        BitField::set(2); // 010 in binary
        $this->assertSame(3, $bitField->getValue()); // 011 in binary
    }

    public function testSetWithAlreadySetBit(): void
    {
        $bitField = new BitField(3); // 011 in binary
        BitField::set(2); // 010 in binary (already set)
        $this->assertSame(3, $bitField->getValue()); // 011 in binary (unchanged)
    }

    public function testClear(): void
    {
        $bitField = new BitField(7); // 111 in binary
        BitField::clear(4); // 100 in binary
        $this->assertSame(3, $bitField->getValue()); // 011 in binary
    }

    public function testClearWithNonSetBit(): void
    {
        $bitField = new BitField(5); // 101 in binary
        BitField::clear(2); // 010 in binary (not set)
        $this->assertSame(5, $bitField->getValue()); // 101 in binary (unchanged)
    }

    public function testAddValue(): void
    {
        $result = $this->bitField->addValue(5, 3);
        $this->assertSame(8, $result);
        $this->assertSame(8, $this->bitField->getValue());
    }

    public function testAddValueWithZero(): void
    {
        $result = $this->bitField->addValue(10, 0);
        $this->assertSame(10, $result);
        $this->assertSame(10, $this->bitField->getValue());
    }

    public function testRemoveValue(): void
    {
        $result = $this->bitField->removeValue(10, 3);
        $this->assertSame(7, $result);
        $this->assertSame(7, $this->bitField->getValue());
    }

    public function testRemoveValueWithZero(): void
    {
        $result = $this->bitField->removeValue(10, 0);
        $this->assertSame(10, $result);
        $this->assertSame(10, $this->bitField->getValue());
    }

    public function testCheckValueWithMatchingBits(): void
    {
        $result = $this->bitField->checkValue(7, 4); // 111 & 100 = 100
        $this->assertTrue($result);
    }

    public function testCheckValueWithNonMatchingBits(): void
    {
        $result = $this->bitField->checkValue(5, 2); // 101 & 010 = 000
        $this->assertFalse($result);
    }

    public function testCheckValueWithExactMatch(): void
    {
        $result = $this->bitField->checkValue(4, 4); // 100 & 100 = 100
        $this->assertTrue($result);
    }

    public function testCheckValueWithZero(): void
    {
        $result = $this->bitField->checkValue(5, 0);
        $this->assertTrue($result); // Any value & 0 = 0, and 0 === 0
    }

    public function testStaticMethodsAffectGlobalState(): void
    {
        // Create first instance
        $bitField1 = new BitField(1);
        $this->assertSame(1, $bitField1->getValue());

        // Create second instance - this overwrites the static value
        $bitField2 = new BitField(2);
        $this->assertSame(2, $bitField1->getValue()); // First instance now shows 2!
        $this->assertSame(2, $bitField2->getValue());

        // Use static method to modify the shared state
        BitField::set(4);

        // Both instances now show the same value due to shared static property
        $this->assertSame(6, $bitField1->getValue()); // 2 | 4 = 6
        $this->assertSame(6, $bitField2->getValue()); // 2 | 4 = 6
    }
}
