<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Command\Catalog\UpdateProductImageByAdmin;

use App\Application\Shared\Port\TransactionalInterface;
use App\Application\Shop\Port\CategoryRepositoryInterface;
use App\Application\Shop\Port\ProductRepositoryInterface;
use App\Application\Shop\ReadModel\ProductView;
use App\Domain\Shop\Catalog\Exception\CatalogDomainException;
use App\Domain\Shop\Catalog\Exception\CategoryNotFoundException;
use App\Domain\Shop\Catalog\Exception\ProductNotFoundException;

final readonly class UpdateProductImageByAdminCommandHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private TransactionalInterface $transactional,
    ) {
    }

    public function handle(UpdateProductImageByAdminCommand $command): UpdateProductImageByAdminOutput
    {
        if (!$command->imageFile->isValid()) {
            throw new CatalogDomainException('Invalid image file.', 400);
        }

        return $this->transactional->transactional(function () use ($command): UpdateProductImageByAdminOutput {
            $product = $this->productRepository->updateImage($command->productId, $command->imageFile);

            if (null === $product) {
                throw new ProductNotFoundException();
            }

            $categoryTree = $this->categoryRepository->findTreeById($product->getCategoryId());
            if (null === $categoryTree) {
                throw new CategoryNotFoundException();
            }

            return new UpdateProductImageByAdminOutput(new ProductView($product, $categoryTree));
        });
    }
}
