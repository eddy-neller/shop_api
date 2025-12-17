<?php

declare(strict_types=1);

namespace App\Presentation\Shop\Presenter\Catalog;

use App\Application\Shop\ReadModel\CategoryTree;
use App\Domain\Shop\Catalog\Model\Category as DomainCategory;
use App\Presentation\Shop\ApiResource\Catalog\CategoryResource;

final readonly class CategoryResourcePresenter
{
    public function toResource(CategoryTree $categoryTree): CategoryResource
    {
        $resource = $this->mapCategory($categoryTree->category);

        $resource->parent = null === $categoryTree->parent ? null : $this->mapCategory($categoryTree->parent);
        $resource->children = array_map(
            fn (DomainCategory $child): CategoryResource => $this->mapCategory($child),
            $categoryTree->children,
        );

        return $resource;
    }

    private function mapCategory(DomainCategory $category): CategoryResource
    {
        $resource = new CategoryResource();

        $resource->id = $category->getId()->toString();
        $resource->title = $category->getTitle()->toString();
        $resource->description = $category->getDescription()?->toString();
        $resource->nbProduct = $category->getProductCount();
        $resource->slug = $category->getSlug()->toString();
        $resource->level = $category->getLevel();
        $resource->createdAt = $category->getCreatedAt();
        $resource->updatedAt = $category->getUpdatedAt();

        return $resource;
    }
}
