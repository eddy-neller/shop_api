<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Command;

/**
 * Marqueur pour toutes les commandes de l'application.
 *
 * Utiliser une interface dédiée permet d'identifier clairement
 * les objets qui transitent par le CommandBus.
 */
interface CommandInterface
{
}
