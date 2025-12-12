<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\User;

use App\Application\Shared\Port\FileInterface;
use App\Application\User\Port\AvatarUploaderInterface;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Infrastructure\Entity\User\User;
use App\Infrastructure\Persistence\Doctrine\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;

final readonly class AvatarUploader implements AvatarUploaderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
    ) {
    }

    public function upload(UserId $userId, FileInterface $file): array
    {
        $user = $this->userRepository->find($userId->toString());

        if (!$user instanceof User) {
            throw new RuntimeException('Utilisateur introuvable.');
        }

        // Convertir FileInterface en File Symfony pour Vich Uploader
        $symfonyFile = new File($file->getPathname());
        $user->setAvatarFile($symfonyFile);
        $this->em->flush();

        return [
            'fileName' => $user->getAvatarName(),
            'url' => $user->getAvatarUrl(),
        ];
    }
}
