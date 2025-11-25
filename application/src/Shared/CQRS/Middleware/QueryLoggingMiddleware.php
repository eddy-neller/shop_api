<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Middleware;

use App\Application\Shared\CQRS\Query\QueryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Middleware de logging pour les requêtes de lecture.
 */
final class QueryLoggingMiddleware implements QueryMiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(QueryInterface $query, callable $next): mixed
    {
        $queryClass = $query::class;
        $startTime = microtime(true);

        $this->logger->info('Dispatching query', [
            'query' => $queryClass,
        ]);

        try {
            $result = $next($query);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->info('Query handled successfully', [
                'query' => $queryClass,
                'duration_ms' => round($duration, 2),
            ]);

            return $result;
        } catch (Throwable $throwable) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->error('Query failed', [
                'query' => $queryClass,
                'duration_ms' => round($duration, 2),
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
