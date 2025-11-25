<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Query;

/**
 * Résout le handler à utiliser pour une requête donnée.
 */
interface QueryHandlerResolverInterface
{
    /**
     * Retourne un callable capable de traiter la requête.
     *
     * @return callable(QueryInterface):mixed
     */
    public function resolve(QueryInterface $query): callable;
}
