<?php

namespace App\Application\Shared\Port;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
