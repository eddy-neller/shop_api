<?php

declare(strict_types=1);

namespace App\Domain\Shop\Tests\Unit\ValueObject\Ordering;

use App\Domain\Shop\Ordering\ValueObject\OrderId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OrderIdTest extends TestCase
{
    private const string UUID = '123e4567-e89b-12d3-a456-426614174000';

    public function testFromStringCreatesValidOrderId(): void
    {
        $id = OrderId::fromString(self::UUID);

        $this->assertSame(self::UUID, $id->toString());
    }

    public function testFromStringThrowsWhenInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OrderId must be a valid UUID.');

        OrderId::fromString('not-a-uuid');
    }
}
