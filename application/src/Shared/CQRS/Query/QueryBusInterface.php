<?php

declare(strict_types=1);

namespace App\Application\Shared\CQRS\Query;

/**
 * Bus pour exécuter les requêtes de lecture.
 */
interface QueryBusInterface
{
    public function dispatch(QueryInterface $query): mixed;
}
