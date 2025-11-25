<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\Event;

use DateTimeImmutable;

interface DomainEventInterface
{
    public function occurredOn(): DateTimeImmutable;

    public function eventName(): string;
}
