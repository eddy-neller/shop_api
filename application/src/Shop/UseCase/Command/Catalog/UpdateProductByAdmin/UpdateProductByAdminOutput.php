<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Command\Catalog\UpdateProductByAdmin;

use App\Application\Shop\ReadModel\ProductView;

final readonly class UpdateProductByAdminOutput
{
    public function __construct(
        public ProductView $productView,
    ) {
    }
}
