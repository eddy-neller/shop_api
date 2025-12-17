<?php

declare(strict_types=1);

namespace App\Domain\User\Tests\Unit\ValueObject\Security;

use App\Domain\User\Security\ValueObject\RoleSet;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RoleSetTest extends TestCase
{
    public function testConstructWithValidRoles(): void
    {
        $roleSet = new RoleSet(['ROLE_USER', 'ROLE_ADMIN']);

        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $roleSet->all());
    }

    public function testConstructWithEmptyArrayUsesDefaultRole(): void
    {
        $roleSet = new RoleSet([]);

        $this->assertSame(['ROLE_USER'], $roleSet->all());
    }

    public function testConstructRemovesDuplicates(): void
    {
        $roleSet = new RoleSet(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);

        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $roleSet->all());
    }

    public function testConstructReindexesArray(): void
    {
        $roleSet = new RoleSet([2 => 'ROLE_USER', 5 => 'ROLE_ADMIN']);
        $roles = $roleSet->all();

        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $roles);
        $this->assertArrayHasKey(0, $roles);
        $this->assertArrayHasKey(1, $roles);
    }

    public function testConstructThrowsExceptionForNonStringRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role invalide.');

        /* @phpstan-ignore argument.type */
        new RoleSet(['ROLE_USER', 123]);
    }

    public function testConstructThrowsExceptionForEmptyRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role invalide.');

        new RoleSet(['ROLE_USER', '']);
    }

    public function testConstructThrowsExceptionForWhitespaceRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role invalide.');

        new RoleSet(['ROLE_USER', '   ']);
    }

    public function testConstructThrowsExceptionForUnauthorizedRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role non autorisé: ROLE_UNKNOWN');

        new RoleSet(['ROLE_USER', 'ROLE_UNKNOWN']);
    }

    public function testAllReturnsAllRoles(): void
    {
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $roleSet = new RoleSet($roles);

        $this->assertSame($roles, $roleSet->all());
    }

    public function testContainsReturnsTrueWhenRoleExists(): void
    {
        $roleSet = new RoleSet(['ROLE_USER', 'ROLE_ADMIN']);

        $this->assertTrue($roleSet->contains('ROLE_USER'));
        $this->assertTrue($roleSet->contains('ROLE_ADMIN'));
    }

    public function testContainsReturnsFalseWhenRoleDoesNotExist(): void
    {
        $roleSet = new RoleSet(['ROLE_USER']);

        $this->assertFalse($roleSet->contains('ROLE_ADMIN'));
    }

    public function testContainsIsStrictComparison(): void
    {
        $roleSet = new RoleSet(['ROLE_USER']);

        $this->assertFalse($roleSet->contains('role_user'));
    }

    public function testAddCreatesNewInstanceWithAddedRole(): void
    {
        $roleSet = new RoleSet(['ROLE_USER']);
        $newRoleSet = $roleSet->add('ROLE_ADMIN');

        $this->assertSame(['ROLE_USER'], $roleSet->all());
        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $newRoleSet->all());
    }

    public function testAddIsImmutable(): void
    {
        $roleSet = new RoleSet(['ROLE_USER']);
        $newRoleSet = $roleSet->add('ROLE_ADMIN');

        $this->assertNotSame($roleSet, $newRoleSet);
    }

    public function testAddDeduplicatesRoles(): void
    {
        $roleSet = new RoleSet(['ROLE_USER', 'ROLE_ADMIN']);
        $newRoleSet = $roleSet->add('ROLE_USER');

        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $newRoleSet->all());
    }

    public function testConstantsAreDefinedCorrectly(): void
    {
        $this->assertSame('ROLE_USER', RoleSet::ROLE_USER);
        $this->assertSame('ROLE_MODERATEUR', RoleSet::ROLE_MODERATEUR);
        $this->assertSame('ROLE_ADMIN', RoleSet::ROLE_ADMIN);
        $this->assertSame('ROLE_SUPER_ADMIN', RoleSet::ROLE_SUPER_ADMIN);
    }
}
