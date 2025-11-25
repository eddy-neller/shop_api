<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\RequestPasswordReset;

use App\Application\Shared\DateIntervalTrait;
use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\ConfigInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\TokenProviderInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\ValueObject\EmailAddress;

final class RequestPasswordResetHandler
{
    use DateIntervalTrait;

    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly ClockInterface $clock,
        private readonly TransactionalInterface $transactional,
        private readonly ConfigInterface $config,
    ) {
    }

    public function handle(RequestPasswordResetCommand $command): void
    {
        $email = new EmailAddress($command->email);
        $user = $this->repository->findByEmail($email);

        if (null === $user) {
            return;
        }

        $this->transactional->transactional(function () use ($user): void {
            $now = $this->clock->now();
            $token = $this->tokenProvider->generateRandomToken();
            $resetPasswordTtl = $this->config->getString('reset_password_token_ttl', 'PT15M');
            $expiresAt = $now->add($this->createInterval($resetPasswordTtl));

            $user->requestPasswordReset($token, $expiresAt, $now);

            $this->repository->save($user);
        });
    }
}
