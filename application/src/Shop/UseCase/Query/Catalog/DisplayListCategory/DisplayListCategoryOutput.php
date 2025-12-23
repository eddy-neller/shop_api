<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Query\Catalog\DisplayListCategory;

use App\Domain\Shop\Catalog\Model\Category;

final readonly class DisplayListCategoryOutput
{
    /**
     * @param list<Category> $categories
     */
    public function __construct(
        public array $categories,
        public int $totalItems,
        public int $totalPages,
    ) {
    }
}
