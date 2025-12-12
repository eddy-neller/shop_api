<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Command;

use App\Application\Shared\CQRS\Middleware\CommandMiddlewareInterface;

final class CommandBus implements CommandBusInterface
{
    /**
     * @param iterable<CommandMiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly iterable $middlewares,
        private readonly CommandHandlerResolverInterface $handlerResolver,
    ) {
    }

    public function dispatch(CommandInterface $command): mixed
    {
        $handler = $this->handlerResolver->resolve($command);

        $pipeline = $this->buildMiddlewarePipeline($handler);

        return $pipeline($command);
    }

    /**
     * Construit la chaîne de middlewares (pattern chain of responsibility).
     */
    private function buildMiddlewarePipeline(callable $handler): callable
    {
        $next = $handler;

        /**
         * On inverse pour que le premier middleware de la liste
         * soit exécuté en premier.
         */
        $middlewares = is_array($this->middlewares) ? $this->middlewares : iterator_to_array($this->middlewares);
        $middlewares = array_reverse($middlewares);

        foreach ($middlewares as $middleware) {
            $currentNext = $next;
            $next = static fn (CommandInterface $command): mixed => $middleware->handle($command, $currentNext);
        }

        return $next;
    }
}
