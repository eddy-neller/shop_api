<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Query\Catalog\DisplayCategory;

use App\Application\Shop\Port\CategoryRepositoryInterface;
use App\Domain\Shop\Catalog\Exception\CategoryNotFoundException;

final readonly class DisplayCategoryQueryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public function handle(DisplayCategoryQuery $query): DisplayCategoryOutput
    {
        $categoryTree = $this->categoryRepository->findTreeById($query->categoryId);

        if (null === $categoryTree) {
            throw new CategoryNotFoundException('Category not found.', 404);
        }

        return new DisplayCategoryOutput($categoryTree);
    }
}
