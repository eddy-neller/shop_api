<?php

declare(strict_types=1);

namespace App\Application\Shop\Port;

use App\Application\Shared\Port\FileInterface;
use App\Domain\Shop\Catalog\Model\Product;
use App\Domain\Shop\Catalog\ValueObject\ProductId;

interface ProductRepositoryInterface
{
    public function nextIdentity(): ProductId;

    public function save(Product $product): void;

    public function delete(Product $product): void;

    public function findById(ProductId $id): ?Product;

    public function updateImage(ProductId $id, FileInterface $file): ?Product;
}
