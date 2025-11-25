<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Middleware;

use App\Application\Shared\CQRS\Query\QueryInterface;

/**
 * Middleware appliqué autour de l'exécution d'une requête.
 */
interface QueryMiddlewareInterface
{
    /**
     * @param callable(QueryInterface):mixed $next
     */
    public function handle(QueryInterface $query, callable $next): mixed;
}
