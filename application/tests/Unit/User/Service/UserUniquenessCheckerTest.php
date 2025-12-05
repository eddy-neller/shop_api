<?php

declare(strict_types=1);

namespace App\Application\Tests\Unit\User\Service;

use App\Application\User\Port\UserRepositoryInterface;
use App\Application\User\Service\UserUniquenessChecker;
use App\Domain\User\Exception\EmailAlreadyUsedException;
use App\Domain\User\Exception\UsernameAlreadyUsedException;
use App\Domain\User\Model\User;
use App\Domain\User\ValueObject\EmailAddress;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\Preferences;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserUniquenessCheckerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;

    private UserUniquenessChecker $checker;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->checker = new UserUniquenessChecker($this->repository);
    }

    public function testEnsureEmailAndUsernameAvailableSucceedsWhenBothAvailable(): void
    {
        $email = new EmailAddress('test@example.com');
        $username = new Username('testuser');

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('findByUsername')
            ->with($username)
            ->willReturn(null);

        $this->checker->ensureEmailAndUsernameAvailable($email, $username);
    }

    public function testEnsureEmailAndUsernameAvailableThrowsWhenEmailAlreadyUsed(): void
    {
        $email = new EmailAddress('existing@example.com');
        $username = new Username('testuser');
        $existingUser = $this->createDomainUser(email: 'existing@example.com', username: 'existinguser');

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingUser);

        $this->repository->expects($this->never())
            ->method('findByUsername');

        $this->expectException(EmailAlreadyUsedException::class);
        $this->expectExceptionMessage('Adresse email déjà utilisée.');

        $this->checker->ensureEmailAndUsernameAvailable($email, $username);
    }

    public function testEnsureEmailAndUsernameAvailableThrowsWhenUsernameAlreadyUsed(): void
    {
        $email = new EmailAddress('test@example.com');
        $username = new Username('existinguser');
        $existingUser = $this->createDomainUser(email: 'test@example.com', username: 'existinguser');

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('findByUsername')
            ->with($username)
            ->willReturn($existingUser);

        $this->expectException(UsernameAlreadyUsedException::class);
        $this->expectExceptionMessage('Nom d\'utilisateur déjà utilisé.');

        $this->checker->ensureEmailAndUsernameAvailable($email, $username);
    }

    private function createDomainUser(string $email, string $username): User
    {
        return User::register(
            UserId::fromString('00000000-0000-4000-8000-000000000000'),
            new Username($username),
            new EmailAddress($email),
            new HashedPassword('hashed-password'),
            new Preferences(),
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
        );
    }
}
