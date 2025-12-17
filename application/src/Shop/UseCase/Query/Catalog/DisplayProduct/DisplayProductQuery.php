<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Query\Catalog\DisplayProduct;

use App\Application\Shared\CQRS\Query\QueryInterface;
use App\Domain\Shop\Catalog\ValueObject\ProductId;

final readonly class DisplayProductQuery implements QueryInterface
{
    public function __construct(
        public ProductId $productId,
    ) {
    }
}
