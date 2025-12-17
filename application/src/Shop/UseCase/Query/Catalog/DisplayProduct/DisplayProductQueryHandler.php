<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Query\Catalog\DisplayProduct;

use App\Application\Shop\Port\CategoryRepositoryInterface;
use App\Application\Shop\Port\ProductRepositoryInterface;
use App\Application\Shop\ReadModel\ProductView;
use App\Domain\Shop\Catalog\Exception\CategoryNotFoundException;
use App\Domain\Shop\Catalog\Exception\ProductNotFoundException;

final readonly class DisplayProductQueryHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public function handle(DisplayProductQuery $query): DisplayProductOutput
    {
        $product = $this->productRepository->findById($query->productId);

        if (null === $product) {
            throw new ProductNotFoundException('Product not found.', 404);
        }

        $categoryTree = $this->categoryRepository->findTreeById($product->getCategoryId());
        if (null === $categoryTree) {
            throw new CategoryNotFoundException('Category not found.', 404);
        }

        return new DisplayProductOutput(new ProductView($product, $categoryTree));
    }
}
