<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Command\Catalog\DeleteCategoryByAdmin;

use App\Application\Shared\Port\ClockInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\Shop\Port\CategoryRepositoryInterface;
use App\Domain\Shop\Catalog\Exception\CategoryNotFoundException;

final readonly class DeleteCategoryByAdminCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $repository,
        private ClockInterface $clock,
        private TransactionalInterface $transactional,
    ) {
    }

    public function handle(DeleteCategoryByAdminCommand $command): void
    {
        $category = $this->repository->findById($command->categoryId);

        if (null === $category) {
            throw new CategoryNotFoundException();
        }

        $this->transactional->transactional(function () use ($category): void {
            $now = $this->clock->now();
            $category->delete($now);

            $this->repository->delete($category);
        });
    }
}
