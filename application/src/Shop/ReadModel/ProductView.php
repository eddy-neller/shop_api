<?php

declare(strict_types=1);

namespace App\Application\Shop\ReadModel;

use App\Domain\Shop\Catalog\Model\Product;

final readonly class ProductView
{
    public function __construct(
        public Product $product,
        public CategoryTree $categoryTree,
    ) {
    }
}
