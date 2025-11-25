<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Command;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Resolver générique qui découvre automatiquement les handlers via convention de nommage.
 *
 * Convention : {Action}Command → {Action}Handler
 * Exemples :
 * - RegisterUserCommand → RegisterUserHandler
 */
final class CommandHandlerResolver implements CommandHandlerResolverInterface
{
    /** @var array<string, callable> Cache des handlers découverts */
    private array $handlerCache = [];

    public function __construct(
        private readonly ContainerInterface $handlerLocator,
    ) {
    }

    public function resolve(CommandInterface $command): callable
    {
        $commandClass = $command::class;

        // Vérifier le cache
        if (isset($this->handlerCache[$commandClass])) {
            return $this->handlerCache[$commandClass];
        }

        // Découvrir le handler via convention
        $handlerClass = $this->discoverHandlerClass($commandClass);

        // Récupérer le handler depuis le ServiceLocator
        if (!$this->handlerLocator->has($handlerClass)) {
            throw new RuntimeException(sprintf('Handler "%s" not found for command "%s". Make sure the handler is registered as a service.', $handlerClass, $commandClass));
        }

        $handler = $this->handlerLocator->get($handlerClass);

        // Vérifier que le handler a bien une méthode handle()
        if (!method_exists($handler, 'handle')) {
            throw new RuntimeException(sprintf('Handler "%s" does not have a "handle" method for command "%s".', $handlerClass, $commandClass));
        }

        // Créer le callable et le mettre en cache
        $callable = static function (CommandInterface $cmd) use ($handler, $commandClass): mixed {
            // Vérification de type pour la sécurité
            if (!$cmd instanceof $commandClass) {
                throw new RuntimeException(sprintf('Command type mismatch. Expected "%s", got "%s".', $commandClass, $cmd::class));
            }

            return $handler->handle($cmd);
        };

        $this->handlerCache[$commandClass] = $callable;

        return $callable;
    }

    /**
     * Découvre le nom de la classe handler à partir du nom de la commande.
     *
     * Convention : {Action}Command → {Action}Handler
     */
    private function discoverHandlerClass(string $commandClass): string
    {
        if (!str_ends_with($commandClass, 'Command')) {
            throw new RuntimeException(sprintf('Command class "%s" must end with "Command" to use auto-discovery.', $commandClass));
        }

        $handlerClass = preg_replace('/Command$/', 'Handler', $commandClass);

        if (!class_exists($handlerClass)) {
            throw new RuntimeException(sprintf('Handler class "%s" not found for command "%s". Expected handler class based on convention: {Action}Command → {Action}Handler.', $handlerClass, $commandClass));
        }

        return $handlerClass;
    }
}
