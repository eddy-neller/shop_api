<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateAvatar;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\AvatarUploaderInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserDomainException;
use App\Domain\User\Exception\UserNotFoundException;

final readonly class UpdateAvatarCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private AvatarUploaderInterface $avatarUploader,
        private ClockInterface $clock,
        private TransactionalInterface $transactional,
    ) {
    }

    public function handle(UpdateAvatarCommand $command): UpdateAvatarOutput
    {
        if (!$command->avatarFile->isValid()) {
            throw new UserDomainException('Fichier avatar invalide.');
        }

        // On garde un lookup Domain ici pour appliquer l'update métier + save (events),
        // même si l'uploader récupère une référence Doctrine pour l'upload Vich.
        $user = $this->repository->findById($command->userId);

        if (null === $user) {
            throw new UserNotFoundException('User not found.', 404);
        }

        return $this->transactional->transactional(function () use ($user, $command): UpdateAvatarOutput {
            $avatar = $this->avatarUploader->upload($command->userId, $command->avatarFile);

            $now = $this->clock->now();
            $user->updateAvatar($avatar, $now);

            $this->repository->save($user);

            return new UpdateAvatarOutput($user);
        });
    }
}
