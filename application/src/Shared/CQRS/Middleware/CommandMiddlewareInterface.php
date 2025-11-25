<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Middleware;

use App\Application\Shared\CQRS\Command\CommandInterface;

/**
 * Middleware appliqué autour de l'exécution d'une commande.
 */
interface CommandMiddlewareInterface
{
    /**
     * @param callable(CommandInterface):mixed $next
     */
    public function handle(CommandInterface $command, callable $next): mixed;
}
