<?php

declare(strict_types=1);

namespace App\Presentation\Shop\Presenter\Catalog;

use App\Application\Shop\Port\ProductImageUrlResolverInterface;
use App\Application\Shop\ReadModel\ProductView;
use App\Presentation\Shop\ApiResource\Catalog\ProductResource;

final readonly class ProductResourcePresenter
{
    public function __construct(
        private ProductImageUrlResolverInterface $productImageUrlResolver,
        private CategoryResourcePresenter $categoryResourcePresenter,
    ) {
    }

    public function toResource(ProductView $productView): ProductResource
    {
        $product = $productView->product;

        $resource = new ProductResource();

        $resource->id = $product->getId()->toString();
        $resource->title = $product->getTitle()->toString();
        $resource->subtitle = $product->getSubtitle()->toString();
        $resource->description = $product->getDescription()->toString();
        $resource->price = round($product->getPrice()->amount() / 100, 2);
        $resource->slug = $product->getSlug()->toString();
        $resource->imageUrl = $this->productImageUrlResolver->resolve($product->getImageName());
        $resource->category = $this->categoryResourcePresenter->toResource($productView->categoryTree);
        $resource->createdAt = $product->getCreatedAt();
        $resource->updatedAt = $product->getUpdatedAt();

        return $resource;
    }
}
