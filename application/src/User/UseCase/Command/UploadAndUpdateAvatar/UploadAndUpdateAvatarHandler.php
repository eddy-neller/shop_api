<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UploadAndUpdateAvatar;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\AvatarUploaderInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserDomainException;
use App\Domain\User\ValueObject\Avatar;

/**
 * Handler qui orchestre l'upload d'avatar et la mise à jour du domaine.
 * Encapsule la logique d'upload technique (Vich) et la mise à jour du domaine.
 */
final class UploadAndUpdateAvatarHandler
{
    public function __construct(
        private readonly AvatarUploaderInterface $avatarUploader,
        private readonly UserRepositoryInterface $repository,
        private readonly ClockInterface $clock,
        private readonly TransactionalInterface $transactional,
    ) {
    }

    public function handle(UploadAndUpdateAvatarCommand $command): UploadAndUpdateAvatarOutput
    {
        $user = $this->repository->findById($command->userId);

        if (null === $user) {
            throw new UserDomainException('Utilisateur introuvable.');
        }

        return $this->transactional->transactional(function () use ($user, $command): UploadAndUpdateAvatarOutput {
            // Upload du fichier via le port (infrastructure gère Vich)
            $uploadResult = $this->avatarUploader->upload($command->userId, $command->avatarFile);

            // Mise à jour du domaine avec les informations du fichier uploadé
            $now = $this->clock->now();
            $avatar = new Avatar(
                fileName: $uploadResult['fileName'],
                url: $uploadResult['url'],
                updatedAt: $now,
            );

            $user->updateAvatar($avatar, $now);

            $this->repository->save($user);

            return new UploadAndUpdateAvatarOutput($user);
        });
    }
}
