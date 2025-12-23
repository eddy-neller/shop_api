<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Query\Catalog\DisplayListProduct;

final readonly class DisplayListProductOutput
{
    public function __construct(
        public array $products,
        public int $totalItems,
        public int $totalPages,
    ) {
    }
}
