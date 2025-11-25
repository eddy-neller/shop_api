<?php

declare(strict_types=1);

namespace App\Application\Tests\Unit\User\UseCase\Query;

use App\Application\User\Port\UserRepositoryInterface;
use App\Application\User\UseCase\Query\DisplayUser\DisplayUserHandler;
use App\Application\User\UseCase\Query\DisplayUser\DisplayUserQuery;
use App\Domain\User\Exception\UserDomainException;
use App\Domain\User\Model\User;
use App\Domain\User\ValueObject\EmailAddress;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\Preferences;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DisplayUserTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;

    private DisplayUserHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new DisplayUserHandler($this->repository);
    }

    public function testHandleReturnsUserOutputWhenUserExists(): void
    {
        $userId = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $query = new DisplayUserQuery($userId);
        $user = $this->createUser($userId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $output = $this->handler->handle($query);

        $this->assertSame($user, $output->user);
    }

    public function testHandleThrowsExceptionWhenUserNotFound(): void
    {
        $userId = UserId::fromString('550e8400-e29b-41d4-a716-446655440001');
        $query = new DisplayUserQuery($userId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(UserDomainException::class);
        $this->expectExceptionMessage('Utilisateur introuvable.');

        $this->handler->handle($query);
    }

    private function createUser(UserId $userId): User
    {
        return User::register(
            id: $userId,
            username: new Username('testuser'),
            email: new EmailAddress('test@example.com'),
            password: new HashedPassword('hash'),
            preferences: Preferences::fromArray(['lang' => 'fr']),
            now: new DateTimeImmutable(),
        );
    }
}
