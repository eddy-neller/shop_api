<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Command\Catalog\UpdateProductByAdmin;

use App\Application\Shared\CQRS\Command\CommandInterface;
use App\Domain\Shop\Catalog\ValueObject\CategoryId;
use App\Domain\Shop\Catalog\ValueObject\ProductId;

final readonly class UpdateProductByAdminCommand implements CommandInterface
{
    public function __construct(
        public ProductId $productId,
        public ?string $title,
        public ?string $subtitle,
        public ?string $description,
        public ?float $price,
        public ?CategoryId $categoryId,
    ) {
    }
}
