<?php

declare(strict_types=1);

namespace App\Application\Shared\Port;

interface UuidGeneratorInterface
{
    public function generate(): string;
}
