<?php

declare(strict_types=1);

namespace App\Application\Tests\Unit\User\UseCase\Command;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\ConfigInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Application\User\UseCase\Command\RegisterWrongPasswordAttempt\RegisterWrongPasswordAttemptCommand;
use App\Application\User\UseCase\Command\RegisterWrongPasswordAttempt\RegisterWrongPasswordAttemptCommandHandler;
use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Model\User;
use App\Domain\User\Preference\ValueObject\Preferences;
use App\Domain\User\Security\ValueObject\HashedPassword;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RegisterWrongPasswordAttemptTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;

    private ClockInterface&MockObject $clock;

    private ConfigInterface&MockObject $config;

    private RegisterWrongPasswordAttemptCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);

        $this->handler = new RegisterWrongPasswordAttemptCommandHandler(
            repository: $this->repository,
            clock: $this->clock,
            config: $this->config,
        );
    }

    public function testHandleIncrementsAttemptsAndBlocksOnThreshold(): void
    {
        $command = new RegisterWrongPasswordAttemptCommand(email: 'john@example.com');
        $user = $this->createUser();

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->with('app.security.max_login_attempts')
            ->willReturn(2);

        $this->clock->expects($this->exactly(2))
            ->method('now')
            ->willReturn(new DateTimeImmutable());

        $this->repository->expects($this->exactly(2))
            ->method('findByEmail')
            ->with(new EmailAddress('john@example.com'))
            ->willReturn($user);

        $this->repository->expects($this->exactly(2))
            ->method('save')
            ->with($user);

        $this->handler->handle($command);
        $this->assertSame(1, $user->getSecurity()->getTotalWrongPassword());

        $this->handler->handle($command);
        $this->assertTrue($user->isLocked());
    }

    public function testHandleDoesNothingWhenUserNotFound(): void
    {
        $command = new RegisterWrongPasswordAttemptCommand(email: 'unknown@example.com');

        $this->config->expects($this->never())->method('get');
        $this->clock->expects($this->never())->method('now');

        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with(new EmailAddress('unknown@example.com'))
            ->willReturn(null);

        $this->repository->expects($this->never())->method('save');

        $this->handler->handle($command);
    }

    private function createUser(): User
    {
        return User::register(
            id: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            username: new Username('john'),
            email: new EmailAddress('john@example.com'),
            password: new HashedPassword('hash'),
            preferences: Preferences::fromArray(['lang' => 'fr']),
            now: new DateTimeImmutable(),
        );
    }
}
