<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Query;

use App\Application\Shared\CQRS\Middleware\QueryMiddlewareInterface;

final class QueryBus implements QueryBusInterface
{
    /**
     * @param iterable<QueryMiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly iterable $middlewares,
        private readonly QueryHandlerResolverInterface $handlerResolver,
    ) {
    }

    public function dispatch(QueryInterface $query): mixed
    {
        $handler = $this->handlerResolver->resolve($query);

        $pipeline = $this->buildMiddlewarePipeline($handler);

        return $pipeline($query);
    }

    /**
     * Construit la chaîne de middlewares (pattern chain of responsibility).
     */
    private function buildMiddlewarePipeline(callable $handler): callable
    {
        $next = $handler;

        $middlewares = is_array($this->middlewares) ? $this->middlewares : iterator_to_array($this->middlewares);
        $middlewares = array_reverse($middlewares);

        foreach ($middlewares as $middleware) {
            $currentNext = $next;
            $next = static fn (QueryInterface $query): mixed => $middleware->handle($query, $currentNext);
        }

        return $next;
    }
}
