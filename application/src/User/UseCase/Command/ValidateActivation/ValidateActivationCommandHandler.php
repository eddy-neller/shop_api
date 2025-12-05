<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\ValidateActivation;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\TokenProviderInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Exception\UserDomainException;
use App\Domain\User\ValueObject\EmailAddress;

final class ValidateActivationCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly ClockInterface $clock,
        private readonly TransactionalInterface $transactional,
    ) {
    }

    public function handle(ValidateActivationCommand $command): void
    {
        $split = $this->tokenProvider->split($command->token);
        $email = new EmailAddress($split['email'] ?? '');
        $rawToken = $split['token'] ?? '';

        $user = $this->repository->findByActivationToken($rawToken);

        if (null === $user || !$user->getEmail()->equals($email)) {
            throw new UserDomainException('Utilisateur introuvable pour ce token.');
        }

        $activeEmail = $user->getActiveEmail();
        $ttl = $activeEmail->getTokenTtl() ?? 0;
        if ($ttl <= 0 || $ttl <= time()) {
            throw new UserDomainException('Token d\'activation expiré.');
        }

        if ($activeEmail->getToken() !== $rawToken) {
            throw new UserDomainException("Token d'activation invalide.");
        }

        $this->transactional->transactional(function () use ($user): void {
            $now = $this->clock->now();
            $user->activate($now);

            $this->repository->save($user);
        });
    }
}
