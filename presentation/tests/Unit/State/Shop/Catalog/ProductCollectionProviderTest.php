<?php

declare(strict_types=1);

namespace App\Presentation\Tests\Unit\State\Shop\Catalog;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\ProviderInterface;
use App\Application\Shop\Port\ProductImageUrlResolverInterface;
use App\Infrastructure\Entity\Shop\Category as DoctrineCategory;
use App\Infrastructure\Entity\Shop\Product as DoctrineProduct;
use App\Presentation\Shop\ApiResource\Catalog\ProductResource;
use App\Presentation\Shop\State\Catalog\Product\ProductCollectionProvider;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;

final class ProductCollectionProviderTest extends TestCase
{
    public function testItMapsProductsToResources(): void
    {
        $category = new DoctrineCategory();
        $category->setId(Uuid::uuid4());
        $category->setTitle('Category title');

        $product = new DoctrineProduct();
        $product->setId(Uuid::uuid4());
        $product->setTitle('Product title');
        $product->setSlug('product-title');
        $product->setPrice(19.99);
        $product->setImageName('product.jpg');
        $product->setCategory($category);
        $product->setCreatedAt(new DateTimeImmutable('2025-01-01 10:00:00'));

        $innerProvider = $this->createMock(ProviderInterface::class);
        $innerProvider
            ->expects($this->once())
            ->method('provide')
            ->willReturn([$product]);

        $imageUrlResolver = $this->createMock(ProductImageUrlResolverInterface::class);
        $imageUrlResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('product.jpg')
            ->willReturn('/uploads/product.jpg');

        $provider = new ProductCollectionProvider(
            provider: $innerProvider,
            productImageUrlResolver: $imageUrlResolver,
        );

        $result = $provider->provide(new GetCollection(name: 'shop-products-col'));

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(ProductResource::class, $result[0]);
        $this->assertSame('Product title', $result[0]->title);
        $this->assertSame('/uploads/product.jpg', $result[0]->imageUrl);
        $this->assertSame('Category title', $result[0]->category->title);
    }

    public function testItReturnsProviderResultWhenNotIterable(): void
    {
        $innerProvider = $this->createMock(ProviderInterface::class);
        $payload = new stdClass();

        $innerProvider
            ->expects($this->once())
            ->method('provide')
            ->willReturn($payload);

        $imageUrlResolver = $this->createMock(ProductImageUrlResolverInterface::class);

        $provider = new ProductCollectionProvider(
            provider: $innerProvider,
            productImageUrlResolver: $imageUrlResolver,
        );

        $result = $provider->provide(new GetCollection(name: 'shop-products-col'));

        $this->assertSame($payload, $result);
    }
}
