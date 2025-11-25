<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Command;

/**
 * Bus pour exécuter les commandes applicatives.
 */
interface CommandBusInterface
{
    public function dispatch(CommandInterface $command): mixed;
}
