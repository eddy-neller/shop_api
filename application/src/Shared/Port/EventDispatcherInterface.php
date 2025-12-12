<?php

declare(strict_types=1);

namespace App\Application\Shared\Port;

use App\Domain\SharedKernel\Event\DomainEventInterface;

interface EventDispatcherInterface
{
    public function dispatch(DomainEventInterface $event): void;

    public function dispatchAll(array $events): void;
}
