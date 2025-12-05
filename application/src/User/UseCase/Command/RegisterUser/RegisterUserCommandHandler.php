<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\RegisterUser;

use App\Application\Shared\DateIntervalTrait;
use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\ConfigInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\User\Port\PasswordHasherInterface;
use App\Application\User\Port\TokenProviderInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Application\User\Port\UserUniquenessCheckerInterface;
use App\Domain\User\Model\User;
use App\Domain\User\ValueObject\EmailAddress;
use App\Domain\User\ValueObject\Preferences;
use App\Domain\User\ValueObject\Username;

final class RegisterUserCommandHandler
{
    use DateIntervalTrait;

    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly ClockInterface $clock,
        private readonly TransactionalInterface $transactional,
        private readonly ConfigInterface $config,
        private readonly UserUniquenessCheckerInterface $uniquenessChecker,
    ) {
    }

    public function handle(RegisterUserCommand $command): RegisterUserOutput
    {
        return $this->transactional->transactional(function () use ($command): RegisterUserOutput {
            $now = $this->clock->now();
            $userId = $this->repository->nextIdentity();

            $username = new Username($command->username);
            $email = new EmailAddress($command->email);

            $this->uniquenessChecker->ensureEmailAndUsernameAvailable($email, $username);

            $preferences = Preferences::fromArray($command->preferences ?? []);
            $hashedPassword = $this->passwordHasher->hash($command->plainPassword);

            $user = User::register(
                id: $userId,
                username: $username,
                email: $email,
                password: $hashedPassword,
                preferences: $preferences,
                now: $now,
            );

            $token = $this->tokenProvider->generateRandomToken();
            $activationTtl = $this->config->getString('register_token_ttl', 'P2D');
            $expiresAt = $now->add($this->createInterval($activationTtl));

            $user->requestActivation($token, $expiresAt, $now);

            $this->repository->save($user);

            return new RegisterUserOutput($user);
        });
    }
}
