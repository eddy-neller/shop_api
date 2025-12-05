<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateAvatar;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserDomainException;
use App\Domain\User\ValueObject\Avatar;

final class UpdateAvatarCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly ClockInterface $clock,
        private readonly TransactionalInterface $transactional,
    ) {
    }

    public function handle(UpdateAvatarCommand $command): UpdateAvatarOutput
    {
        $user = $this->repository->findById($command->userId);

        if (null === $user) {
            throw new UserDomainException('Utilisateur introuvable.');
        }

        return $this->transactional->transactional(function () use ($user, $command): UpdateAvatarOutput {
            $now = $this->clock->now();
            $avatar = new Avatar(
                fileName: $command->avatarFileName,
                url: $command->avatarUrl,
                updatedAt: $now,
            );

            $user->updateAvatar($avatar, $now);

            $this->repository->save($user);

            return new UpdateAvatarOutput($user);
        });
    }
}
