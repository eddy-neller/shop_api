<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\PasswordResetRequestInput;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Security\RateLimitGuard;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Throwable;

/**
 * @codeCoverageIgnore
 */
readonly class PasswordResetRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private RequestStack $requestStack,
        private UserManager $userManager,
        private LockFactory $lockFactory,
        #[Autowire(service: 'limiter.reset_password_ip')]
        private RateLimiterFactory $ipLimiter,
        #[Autowire(service: 'limiter.reset_password_email')]
        private RateLimiterFactory $emailLimiter,
        private RateLimitGuard $rateLimitGuard,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof PasswordResetRequestInput) {
            throw new LogicException(InfoCodes::INTERNAL['INVALID_INPUT']);
        }

        $email = $data->email;
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';

        $this->rateLimitGuard->consumeOrThrow($this->ipLimiter->create($ip), InfoCodes::USER['TOO_MANY_REQUESTS_IP']);
        $this->rateLimitGuard->consumeOrThrow($this->emailLimiter->create('' !== $email ? $email : 'empty'), InfoCodes::USER['TOO_MANY_REQUESTS_EMAIL']);

        // Recherche utilisateur — on ne fuite rien si absent
        $user = $email ? $this->userRepository->findOneBy(['email' => $email]) : null;
        if (!$user instanceof User) {
            return;
        }

        // Verrou applicatif par email pour éviter les doubles envois (concurrence)
        $lock = $this->lockFactory->createLock('reset-password:' . sha1($email), 30.0); // TTL 30s
        if (!$lock->acquire()) {
            return;
        }

        try {
            $this->userManager->requestPasswordReset($user);
        } catch (Throwable $e) {
            $this->logger?->error('reset_password_request_failed', ['email' => $email, 'ex' => $e]);
        } finally {
            $lock->release();
        }
    }
}
