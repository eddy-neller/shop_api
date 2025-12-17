<?php

declare(strict_types=1);

namespace App\Application\Shop\ReadModel;

use App\Domain\Shop\Catalog\Model\Category;

final readonly class CategoryTree
{
    /**
     * @param Category[] $children
     */
    public function __construct(
        public Category $category,
        public ?Category $parent,
        public array $children,
    ) {
    }
}
