<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Query;

/**
 * Résout le handler à utiliser pour une requête donnée.
 */
interface QueryHandlerResolverInterface
{
    public function resolve(QueryInterface $query): callable;
}
