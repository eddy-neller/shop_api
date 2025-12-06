<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\RegisterWrongPasswordAttempt;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\ConfigInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Identity\ValueObject\EmailAddress;

final readonly class RegisterWrongPasswordAttemptCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private ClockInterface $clock,
        private ConfigInterface $config,
    ) {
    }

    public function handle(RegisterWrongPasswordAttemptCommand $command): void
    {
        $email = new EmailAddress($command->email);
        $user = $this->repository->findByEmail($email);

        if (null === $user) {
            return;
        }

        $maxAttempts = (int) $this->config->get('app.security.max_login_attempts');
        $user->registerWrongPasswordAttempt($maxAttempts, $this->clock->now());

        $this->repository->save($user);
    }
}
