<?php

declare(strict_types=1);

namespace App\Domain\Shop\Tests\Unit\ValueObject\Ordering;

use App\Domain\Shop\Ordering\ValueObject\CarrierSelection;
use App\Domain\Shop\Shared\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CarrierSelectionTest extends TestCase
{
    public function testFromValuesTrimsName(): void
    {
        $selection = CarrierSelection::fromValues('  Colissimo  ', Money::fromInt(500, 'eur'));

        $this->assertSame('Colissimo', $selection->getName());
        $this->assertSame(500, $selection->getPrice()->amount());
        $this->assertSame('EUR', $selection->getPrice()->currency());
    }

    public function testFromValuesThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Carrier name cannot be empty.');

        CarrierSelection::fromValues('   ', Money::fromInt(500, 'EUR'));
    }
}
