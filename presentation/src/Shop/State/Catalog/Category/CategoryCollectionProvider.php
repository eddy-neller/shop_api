<?php

declare(strict_types=1);

namespace App\Presentation\Shop\State\Catalog\Category;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Infrastructure\Entity\Shop\Category as DoctrineCategory;
use App\Presentation\Shared\State\PaginatedCollectionProvider;
use App\Presentation\Shop\ApiResource\Catalog\CategoryResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CategoryCollectionProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: PaginatedCollectionProvider::class)]
        private ProviderInterface $provider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $result = $this->provider->provide($operation, $uriVariables, $context);

        if (!is_iterable($result)) {
            return $result;
        }

        return array_map(function (mixed $item): CategoryResource {
            return $this->mapToResource($item);
        }, $result);
    }

    private function mapToResource(DoctrineCategory $user): CategoryResource
    {
        $resource = new CategoryResource();

        $resource->id = $user->getId()->toString();
        $resource->title = $user->getTitle();
        $resource->slug = $user->getSlug();
        $resource->nbProduct = $user->getNbProduct();
        $resource->level = $user->getLevel();
        $resource->createdAt = $user->getCreatedAt();

        return $resource;
    }
}
