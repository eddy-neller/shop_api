<?php

declare(strict_types=1);

namespace App\Domain\Shop\Tests\Unit\ValueObject\Ordering;

use App\Domain\Shop\Customer\Model\Address;
use App\Domain\Shop\Customer\ValueObject\AddressId;
use App\Domain\Shop\Customer\ValueObject\CustomerId;
use App\Domain\Shop\Ordering\ValueObject\DeliveryAddress;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DeliveryAddressTest extends TestCase
{
    private const string UUID = '123e4567-e89b-12d3-a456-426614174000';

    public function testFromValuesTrimsAllFields(): void
    {
        $address = DeliveryAddress::fromValues(
            label: '  Home  ',
            firstname: '  John  ',
            lastname: '  Doe  ',
            street: '  1 Main St  ',
            zipCode: '  75001  ',
            city: '  Paris  ',
            country: '  France  ',
            phone: '  +33123456789  ',
            company: '  ACME  ',
        );

        $this->assertSame('Home', $address->getLabel());
        $this->assertSame('John', $address->getFirstname());
        $this->assertSame('Doe', $address->getLastname());
        $this->assertSame('ACME', $address->getCompany());
        $this->assertSame('1 Main St', $address->getStreet());
        $this->assertSame('75001', $address->getZipCode());
        $this->assertSame('Paris', $address->getCity());
        $this->assertSame('France', $address->getCountry());
        $this->assertSame('+33123456789', $address->getPhone());
    }

    public function testFromValuesAcceptsNullCompany(): void
    {
        $address = DeliveryAddress::fromValues(
            label: 'Home',
            firstname: 'John',
            lastname: 'Doe',
            street: '1 Main St',
            zipCode: '75001',
            city: 'Paris',
            country: 'France',
            phone: '+33123456789',
        );

        $this->assertNull($address->getCompany());
    }

    public function testFromValuesThrowsWhenFirstnameTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Firstname must be between 2 and 32 characters.');

        DeliveryAddress::fromValues(
            label: 'Home',
            firstname: 'J',
            lastname: 'Doe',
            street: '1 Main St',
            zipCode: '75001',
            city: 'Paris',
            country: 'France',
            phone: '+33123456789',
        );
    }

    public function testFromValuesThrowsWhenCompanyTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Company must be between 2 and 50 characters.');

        DeliveryAddress::fromValues(
            label: 'Home',
            firstname: 'John',
            lastname: 'Doe',
            street: '1 Main St',
            zipCode: '75001',
            city: 'Paris',
            country: 'France',
            phone: '+33123456789',
            company: 'A',
        );
    }

    public function testFromAddressBuildsFromAddressModel(): void
    {
        $now = new DateTimeImmutable('2025-01-01T00:00:00+00:00');

        $addressModel = Address::create(
            id: AddressId::fromString(self::UUID),
            ownerId: CustomerId::fromString(self::UUID),
            label: 'Home',
            firstname: 'John',
            lastname: 'Doe',
            street: '1 Main St',
            zipCode: '75001',
            city: 'Paris',
            country: 'France',
            phone: '+33123456789',
            now: $now,
            company: 'ACME',
        );

        $delivery = DeliveryAddress::fromAddress($addressModel);

        $this->assertSame('Home', $delivery->getLabel());
        $this->assertSame('John', $delivery->getFirstname());
        $this->assertSame('Doe', $delivery->getLastname());
        $this->assertSame('ACME', $delivery->getCompany());
        $this->assertSame('1 Main St', $delivery->getStreet());
        $this->assertSame('75001', $delivery->getZipCode());
        $this->assertSame('Paris', $delivery->getCity());
        $this->assertSame('France', $delivery->getCountry());
        $this->assertSame('+33123456789', $delivery->getPhone());
    }
}
