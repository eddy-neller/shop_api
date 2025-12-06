<?php

declare(strict_types=1);

namespace App\Application\Tests\Unit\User\UseCase\Command;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\FileInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\AvatarUploaderInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Application\User\UseCase\Command\UploadAndUpdateAvatar\UploadAndUpdateAvatarCommand;
use App\Application\User\UseCase\Command\UploadAndUpdateAvatar\UploadAndUpdateAvatarCommandHandler;
use App\Domain\User\Exception\UserDomainException;
use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Model\User;
use App\Domain\User\Preference\ValueObject\Preferences;
use App\Domain\User\Security\ValueObject\HashedPassword;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UploadAndUpdateAvatarTest extends TestCase
{
    private AvatarUploaderInterface&MockObject $avatarUploader;

    private UserRepositoryInterface&MockObject $repository;

    private ClockInterface&MockObject $clock;

    private TransactionalInterface&MockObject $transactional;

    private UploadAndUpdateAvatarCommandHandler $handler;

    protected function setUp(): void
    {
        $this->avatarUploader = $this->createMock(AvatarUploaderInterface::class);
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->transactional = $this->createMock(TransactionalInterface::class);
        $this->handler = new UploadAndUpdateAvatarCommandHandler(
            $this->avatarUploader,
            $this->repository,
            $this->clock,
            $this->transactional,
        );
    }

    public function testHandleUploadsAndUpdatesAvatarWhenUserExists(): void
    {
        $userId = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $user = $this->createUser($userId);
        $avatarFile = $this->createMock(FileInterface::class);
        $command = new UploadAndUpdateAvatarCommand($userId, $avatarFile);
        $now = new DateTimeImmutable();
        $uploadResult = [
            'fileName' => 'avatar-123.jpg',
            'url' => '/uploads/avatars/avatar-123.jpg',
        ];

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->avatarUploader->expects($this->once())
            ->method('upload')
            ->with($userId, $avatarFile)
            ->willReturn($uploadResult);

        $this->clock->expects($this->once())
            ->method('now')
            ->willReturn($now);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->transactional->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (callable $callback) {
                return $callback();
            });

        $output = $this->handler->handle($command);

        $this->assertSame($user, $output->user);
        $this->assertSame($uploadResult['fileName'], $user->getAvatar()->fileName());
        $this->assertSame($uploadResult['url'], $user->getAvatar()->url());
        $this->assertEquals($now, $user->getAvatar()->updatedAt());
    }

    public function testHandleThrowsExceptionWhenUserNotFound(): void
    {
        $userId = UserId::fromString('550e8400-e29b-41d4-a716-446655440001');
        $avatarFile = $this->createMock(FileInterface::class);
        $command = new UploadAndUpdateAvatarCommand($userId, $avatarFile);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);

        $this->avatarUploader->expects($this->never())
            ->method('upload');

        $this->expectException(UserDomainException::class);
        $this->expectExceptionMessage('Utilisateur introuvable.');

        $this->handler->handle($command);
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
