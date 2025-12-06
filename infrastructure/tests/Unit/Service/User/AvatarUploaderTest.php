<?php

declare(strict_types=1);

namespace App\Infrastructure\Tests\Unit\Service\User;

use App\Application\Shared\Port\FileInterface;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Infrastructure\Entity\User\User;
use App\Infrastructure\Persistence\Doctrine\User\UserRepository;
use App\Infrastructure\Service\User\AvatarUploader;
use App\Infrastructure\Tests\Unit\BaseTest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;

final class AvatarUploaderTest extends BaseTest
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $em;

    /** @var UserRepository&MockObject */
    private UserRepository $userRepository;

    private AvatarUploader $avatarUploader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->avatarUploader = new AvatarUploader(
            $this->em,
            $this->userRepository,
        );
    }

    public function testUploadSuccess(): void
    {
        $userId = UserId::fromString($this->faker->uuid());
        $user = $this->createUser();
        $avatarName = 'avatar-' . $this->faker->uuid() . '.jpg';
        $avatarUrl = '/uploads/avatars/' . $avatarName;

        // Créer un fichier temporaire réel avec la méthode utilitaire
        $tmpFile = $this->createTempFile('fake image content', 'avatar_test_');

        // Mock du fichier
        $file = $this->createMock(FileInterface::class);
        $file->method('getPathname')->willReturn($tmpFile);

        // Configuration de l'utilisateur
        $user->setAvatarName($avatarName);
        $user->setAvatarUrl($avatarUrl);

        // Configuration du repository
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId->toString())
            ->willReturn($user);

        // Configuration de l'EntityManager
        $this->em
            ->expects($this->once())
            ->method('flush');

        // Exécution
        $result = $this->avatarUploader->upload($userId, $file);

        // Nettoyage
        $this->deleteTempFile($tmpFile);

        // Assertions
        $this->assertArrayHasKey('fileName', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals($avatarName, $result['fileName']);
        $this->assertEquals($avatarUrl, $result['url']);
    }

    public function testUploadThrowsExceptionWhenUserNotFound(): void
    {
        $userId = UserId::fromString($this->faker->uuid());

        // Mock du fichier
        $file = $this->createMock(FileInterface::class);
        $file->method('getPathname')->willReturn('/tmp/test-avatar.jpg');

        // Configuration du repository pour retourner null
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId->toString())
            ->willReturn(null);

        // L'EntityManager ne doit pas être appelé
        $this->em
            ->expects($this->never())
            ->method('flush');

        // Assertions de l'exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Utilisateur introuvable.');

        // Exécution
        $this->avatarUploader->upload($userId, $file);
    }

    public function testUploadWithNullAvatarUrl(): void
    {
        $userId = UserId::fromString($this->faker->uuid());
        $user = $this->createUser();
        $avatarName = 'avatar-' . $this->faker->uuid() . '.jpg';

        // Créer un fichier temporaire réel avec la méthode utilitaire
        $tmpFile = $this->createTempFile('fake image content', 'avatar_test_');

        // Mock du fichier
        $file = $this->createMock(FileInterface::class);
        $file->method('getPathname')->willReturn($tmpFile);

        // Configuration de l'utilisateur avec avatarUrl null
        $user->setAvatarName($avatarName);
        $user->setAvatarUrl(null);

        // Configuration du repository
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId->toString())
            ->willReturn($user);

        // Configuration de l'EntityManager
        $this->em
            ->expects($this->once())
            ->method('flush');

        // Exécution
        $result = $this->avatarUploader->upload($userId, $file);

        // Nettoyage
        $this->deleteTempFile($tmpFile);

        // Assertions
        $this->assertArrayHasKey('fileName', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals($avatarName, $result['fileName']);
        $this->assertNull($result['url']);
    }

    public function testUploadSetsAvatarFileOnUser(): void
    {
        $userId = UserId::fromString($this->faker->uuid());
        $user = $this->createMock(User::class);

        // Créer un fichier temporaire réel avec la méthode utilitaire
        $tmpFile = $this->createTempFile('fake image content', 'avatar_test_');

        // Mock du fichier
        $file = $this->createMock(FileInterface::class);
        $file->method('getPathname')->willReturn($tmpFile);

        // Configuration du repository
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId->toString())
            ->willReturn($user);

        // Vérification que setAvatarFile est appelé avec une instance de File
        $user->expects($this->once())
            ->method('setAvatarFile')
            ->with($this->callback(function ($arg) use ($tmpFile) {
                $this->assertInstanceOf(File::class, $arg);
                $this->assertSame($tmpFile, $arg->getPathname());

                return true;
            }));

        $user->method('getAvatarName')->willReturn('test-avatar.jpg');
        $user->method('getAvatarUrl')->willReturn('/uploads/avatars/test-avatar.jpg');

        // Configuration de l'EntityManager
        $this->em
            ->expects($this->once())
            ->method('flush');

        // Exécution
        $this->avatarUploader->upload($userId, $file);

        // Nettoyage
        $this->deleteTempFile($tmpFile);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUsername($this->faker->userName());
        $user->setEmail($this->faker->email());
        $user->setPassword($this->faker->password());
        $user->setRoles(['ROLE_USER']);

        return $user;
    }
}
