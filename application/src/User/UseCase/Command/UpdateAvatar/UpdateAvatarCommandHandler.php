<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateAvatar;

use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserDomainException;
use App\Domain\User\Exception\UserNotFoundException;

final readonly class UpdateAvatarCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private TransactionalInterface $transactional,
    ) {
    }

    public function handle(UpdateAvatarCommand $command): UpdateAvatarOutput
    {
        if (!$command->avatarFile->isValid()) {
            throw new UserDomainException('Fichier avatar invalide.');
        }

        return $this->transactional->transactional(function () use ($command): UpdateAvatarOutput {
            $user = $this->repository->updateAvatar($command->userId, $command->avatarFile);

            if (null === $user) {
                throw new UserNotFoundException('User not found.', 404);
            }

            return new UpdateAvatarOutput($user);
        });
    }
}
