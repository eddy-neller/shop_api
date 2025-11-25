<?php

namespace App\Infrastructure\Service;

use App\Application\Shared\Port\ClockInterface;
use DateTimeImmutable;

final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
