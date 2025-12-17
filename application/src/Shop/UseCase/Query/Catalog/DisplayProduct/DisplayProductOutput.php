<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Query\Catalog\DisplayProduct;

use App\Application\Shop\ReadModel\ProductView;

final readonly class DisplayProductOutput
{
    public function __construct(
        public ProductView $productView,
    ) {
    }
}
