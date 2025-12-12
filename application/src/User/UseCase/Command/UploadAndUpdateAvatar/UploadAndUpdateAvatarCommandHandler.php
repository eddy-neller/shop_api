<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UploadAndUpdateAvatar;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\AvatarUploaderInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserDomainException;
use App\Domain\User\Profile\ValueObject\Avatar;

final readonly class UploadAndUpdateAvatarCommandHandler
{
    public function __construct(
        private AvatarUploaderInterface $avatarUploader,
        private UserRepositoryInterface $repository,
        private ClockInterface $clock,
        private TransactionalInterface $transactional,
    ) {
    }

    public function handle(UploadAndUpdateAvatarCommand $command): UploadAndUpdateAvatarOutput
    {
        $user = $this->repository->findById($command->userId);

        if (null === $user) {
            throw new UserDomainException('Utilisateur introuvable.');
        }

        return $this->transactional->transactional(function () use ($user, $command): UploadAndUpdateAvatarOutput {
            $uploadResult = $this->avatarUploader->upload($command->userId, $command->avatarFile);

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
