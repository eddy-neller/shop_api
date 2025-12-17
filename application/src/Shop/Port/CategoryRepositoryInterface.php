<?php

declare(strict_types=1);

namespace App\Application\Shop\Port;

use App\Application\Shop\ReadModel\CategoryTree;
use App\Domain\Shop\Catalog\Model\Category;
use App\Domain\Shop\Catalog\ValueObject\CategoryId;

interface CategoryRepositoryInterface
{
    public function nextIdentity(): CategoryId;

    public function save(Category $category): void;

    public function delete(Category $category): void;

    public function findById(CategoryId $id): ?Category;

    public function findTreeById(CategoryId $id): ?CategoryTree;
}
