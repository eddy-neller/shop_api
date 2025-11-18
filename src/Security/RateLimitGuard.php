<?php

namespace App\Security;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;

final class RateLimitGuard
{
    public function consumeOrThrow(LimiterInterface $limiter, string $message): void
    {
        $limit = $limiter->consume(1);
        if ($limit->isAccepted()) {
            return;
        }

        throw new TooManyRequestsHttpException($this->retryAfter($limit), $message);
    }

    private function retryAfter(RateLimit $limit): int
    {
        $ts = $limit->getRetryAfter()->getTimestamp();

        return null !== $ts ? max(1, $ts - time()) : 60;
    }
}
