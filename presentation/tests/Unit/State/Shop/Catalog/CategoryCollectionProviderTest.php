<?php

declare(strict_types=1);

namespace App\Presentation\Tests\Unit\State\Shop\Catalog;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\ProviderInterface;
use App\Infrastructure\Entity\Shop\Category as DoctrineCategory;
use App\Presentation\Shop\ApiResource\Catalog\CategoryResource;
use App\Presentation\Shop\State\Catalog\Category\CategoryCollectionProvider;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;

final class CategoryCollectionProviderTest extends TestCase
{
    public function testItMapsCategoriesToResources(): void
    {
        $category = new DoctrineCategory();
        $category->setId(Uuid::uuid4());
        $category->setTitle('Category title');
        $category->setSlug('category-title');
        $category->setNbProduct(3);
        $category->setLevel(1);
        $category->setCreatedAt(new DateTimeImmutable('2025-01-01 10:00:00'));
        $category->setUpdatedAt(new DateTimeImmutable('2025-02-01 10:00:00'));

        $innerProvider = $this->createMock(ProviderInterface::class);
        $innerProvider
            ->expects($this->once())
            ->method('provide')
            ->willReturn([$category]);

        $provider = new CategoryCollectionProvider($innerProvider);

        $result = $provider->provide(new GetCollection(name: 'shop-categories-col'));

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(CategoryResource::class, $result[0]);
        $this->assertSame('Category title', $result[0]->title);
        $this->assertSame('category-title', $result[0]->slug);
        $this->assertSame(3, $result[0]->nbProduct);
        $this->assertSame(1, $result[0]->level);
    }

    public function testItReturnsProviderResultWhenNotIterable(): void
    {
        $innerProvider = $this->createMock(ProviderInterface::class);
        $payload = new stdClass();

        $innerProvider
            ->expects($this->once())
            ->method('provide')
            ->willReturn($payload);

        $provider = new CategoryCollectionProvider($innerProvider);

        $result = $provider->provide(new GetCollection(name: 'shop-categories-col'));

        $this->assertSame($payload, $result);
    }
}
