<?php

declare(strict_types=1);

namespace App\Presentation\Shop\State\Catalog\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Shop\Port\ProductImageUrlResolverInterface;
use App\Infrastructure\Entity\Shop\Category as DoctrineCategory;
use App\Infrastructure\Entity\Shop\Product as DoctrineProduct;
use App\Presentation\Shared\State\PaginatedCollectionProvider;
use App\Presentation\Shop\ApiResource\Catalog\CategoryResource;
use App\Presentation\Shop\ApiResource\Catalog\ProductResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProductCollectionProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: PaginatedCollectionProvider::class)]
        private ProviderInterface $provider,
        private ProductImageUrlResolverInterface $productImageUrlResolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $result = $this->provider->provide($operation, $uriVariables, $context);

        if (!is_iterable($result)) {
            return $result;
        }

        return array_map(function (mixed $item): ProductResource {
            return $this->mapToResource($item);
        }, $result);
    }

    private function mapToResource(DoctrineProduct $product): ProductResource
    {
        $resource = new ProductResource();

        $resource->id = $product->getId()->toString();
        $resource->title = $product->getTitle();
        $resource->slug = $product->getSlug();
        $resource->price = round($product->getPrice() / 100, 2);
        $resource->slug = $product->getSlug();
        $resource->imageUrl = $this->productImageUrlResolver->resolve($product->getImageName());
        $resource->category = $this->mapCategoryToResource($product->getCategory());
        $resource->createdAt = $product->getCreatedAt();

        return $resource;
    }

    private function mapCategoryToResource(DoctrineCategory $user): CategoryResource
    {
        $resource = new CategoryResource();

        $resource->id = $user->getId()->toString();
        $resource->title = $user->getTitle();

        return $resource;
    }
}
