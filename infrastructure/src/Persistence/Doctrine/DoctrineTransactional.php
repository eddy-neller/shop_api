<?php

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Shared\Port\TransactionalInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransactional implements TransactionalInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function transactional(callable $operation)
    {
        return $this->entityManager->wrapInTransaction($operation);
    }
}
