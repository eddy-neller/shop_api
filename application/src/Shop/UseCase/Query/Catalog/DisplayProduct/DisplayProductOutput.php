<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Query\Catalog\DisplayProduct;

use App\Application\Shop\ReadModel\ProductItem;

final readonly class DisplayProductOutput
{
    public function __construct(
        public ProductItem $productItem,
    ) {
    }
}
