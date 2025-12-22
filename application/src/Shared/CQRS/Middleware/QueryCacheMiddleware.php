<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Middleware;

use App\Application\Shared\CQRS\Query\CacheableQueryInterface;
use App\Application\Shared\CQRS\Query\QueryInterface;
use App\Application\Shared\Port\QueryCacheInterface;

final readonly class QueryCacheMiddleware implements QueryMiddlewareInterface
{
    public function __construct(
        private QueryCacheInterface $cache,
    ) {
    }

    public function handle(QueryInterface $query, callable $next): mixed
    {
        if (!$query instanceof CacheableQueryInterface) {
            return $next($query);
        }

        return $this->cache->get(
            key: $query->cacheKey(),
            ttlSeconds: $query->cacheTtl(),
            tags: $query->cacheTags(),
            callback: static fn (): mixed => $next($query),
        );
    }
}
