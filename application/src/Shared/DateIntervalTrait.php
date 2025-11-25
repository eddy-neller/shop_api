<?php

declare(strict_types=1);

namespace App\Application\Shared;

use DateInterval;
use RuntimeException;
use Throwable;

trait DateIntervalTrait
{
    private function createInterval(string $spec): DateInterval
    {
        try {
            return new DateInterval($spec);
        } catch (Throwable $throwable) {
            throw new RuntimeException(sprintf('Invalid interval spec "%s"', $spec), 0, $throwable);
        }
    }
}
