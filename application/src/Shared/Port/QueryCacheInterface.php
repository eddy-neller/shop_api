<?php

declare(strict_types=1);

namespace App\Application\Shared\Port;

interface QueryCacheInterface
{
    public function get(string $key, int $ttlSeconds, array $tags, callable $callback): mixed;

    public function invalidateTags(array $tags): void;
}
