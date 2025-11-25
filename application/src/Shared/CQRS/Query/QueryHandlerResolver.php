<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Query;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Resolver générique qui découvre automatiquement les query handlers via convention de nommage.
 *
 * Convention : {Action}Query → {Action}Handler
 * Exemples :
 * - DisplayUserQuery → DisplayUserHandler
 */
final class QueryHandlerResolver implements QueryHandlerResolverInterface
{
    /** @var array<string, callable> Cache des handlers découverts */
    private array $handlerCache = [];

    public function __construct(
        private readonly ContainerInterface $handlerLocator,
    ) {
    }

    public function resolve(QueryInterface $query): callable
    {
        $queryClass = $query::class;

        // Vérifier le cache
        if (isset($this->handlerCache[$queryClass])) {
            return $this->handlerCache[$queryClass];
        }

        // Découvrir le handler via convention
        $handlerClass = $this->discoverHandlerClass($queryClass);

        // Récupérer le handler depuis le ServiceLocator
        if (!$this->handlerLocator->has($handlerClass)) {
            throw new RuntimeException(sprintf('Query handler "%s" not found for query "%s". Make sure the handler is registered as a service.', $handlerClass, $queryClass));
        }

        $handler = $this->handlerLocator->get($handlerClass);

        // Vérifier que le handler a bien une méthode handle()
        if (!method_exists($handler, 'handle')) {
            throw new RuntimeException(sprintf('Query handler "%s" does not have a "handle" method for query "%s".', $handlerClass, $queryClass));
        }

        // Créer le callable et le mettre en cache
        $callable = static function (QueryInterface $qry) use ($handler, $queryClass): mixed {
            // Vérification de type pour la sécurité
            if (!$qry instanceof $queryClass) {
                throw new RuntimeException(sprintf('Query type mismatch. Expected "%s", got "%s".', $queryClass, $qry::class));
            }

            return $handler->handle($qry);
        };

        $this->handlerCache[$queryClass] = $callable;

        return $callable;
    }

    /**
     * Découvre le nom de la classe handler à partir du nom de la query.
     *
     * Convention : {Action}Query → {Action}Handler
     */
    private function discoverHandlerClass(string $queryClass): string
    {
        if (!str_ends_with($queryClass, 'Query')) {
            throw new RuntimeException(sprintf('Query class "%s" must end with "Query" to use auto-discovery.', $queryClass));
        }

        $handlerClass = preg_replace('/Query$/', 'Handler', $queryClass);

        if (!class_exists($handlerClass)) {
            throw new RuntimeException(sprintf('Query handler class "%s" not found for query "%s". Expected handler class based on convention: {Action}Query → {Action}Handler.', $handlerClass, $queryClass));
        }

        return $handlerClass;
    }
}
