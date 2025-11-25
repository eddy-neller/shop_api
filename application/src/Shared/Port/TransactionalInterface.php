<?php

namespace App\Application\Shared\Port;

interface TransactionalInterface
{
    /**
     * @template T
     *
     * @param callable():T $operation
     *
     * @return T
     */
    public function transactional(callable $operation);
}
