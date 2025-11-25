<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Command;

/**
 * Résout le handler à utiliser pour une commande donnée.
 */
interface CommandHandlerResolverInterface
{
    /**
     * Retourne un callable capable de traiter la commande.
     *
     * @return callable(CommandInterface):mixed
     */
    public function resolve(CommandInterface $command): callable;
}
