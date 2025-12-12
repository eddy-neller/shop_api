<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Query;

use Psr\Container\ContainerInterface;
use RuntimeException;

final class QueryHandlerResolver implements QueryHandlerResolverInterface
{
    private array $handlerCache = [];

    public function __construct(
        private readonly ContainerInterface $handlerLocator,
    ) {
    }

    public function resolve(QueryInterface $query): callable
    {
        $queryClass = $query::class;

        if (isset($this->handlerCache[$queryClass])) {
            return $this->handlerCache[$queryClass];
        }

        $handlerClass = $this->discoverHandlerClass($queryClass);

        // TODO: Récupérer le handler depuis le ServiceLocator (à changer)
        if (!$this->handlerLocator->has($handlerClass)) {
            throw new RuntimeException(sprintf('Query handler "%s" not found for query "%s". Make sure the handler is registered as a service.', $handlerClass, $queryClass));
        }

        $handler = $this->handlerLocator->get($handlerClass);

        if (!method_exists($handler, 'handle')) {
            throw new RuntimeException(sprintf('Query handler "%s" does not have a "handle" method for query "%s".', $handlerClass, $queryClass));
        }

        // Créer le callable et le mettre en cache
        $callable = static function (QueryInterface $qry) use ($handler, $queryClass): mixed {
            if (!$qry instanceof $queryClass) {
                throw new RuntimeException(sprintf('Query type mismatch. Expected "%s", got "%s".', $queryClass, $qry::class));
            }

            return $handler->handle($qry);
        };

        $this->handlerCache[$queryClass] = $callable;

        return $callable;
    }

    private function discoverHandlerClass(string $queryClass): string
    {
        if (!str_ends_with($queryClass, 'Query')) {
            throw new RuntimeException(sprintf('Query class "%s" must end with "Query" to use auto-discovery.', $queryClass));
        }

        $handlerClass = preg_replace('/Query$/', 'QueryHandler', $queryClass);

        if (!class_exists($handlerClass)) {
            throw new RuntimeException(sprintf('Query handler class "%s" not found for query "%s". Expected handler class based on convention: {Action}Query → {Action}QueryHandler.', $handlerClass, $queryClass));
        }

        return $handlerClass;
    }
}
