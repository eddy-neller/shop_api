<?php

declare(strict_types=1);

namespace App\Application\Tests\Unit\Shop\UseCase\Command;

use App\Application\Shared\Port\FileInterface;
use App\Application\Shared\Port\TransactionalInterface;
use App\Application\Shop\Port\CategoryRepositoryInterface;
use App\Application\Shop\Port\ProductRepositoryInterface;
use App\Application\Shop\ReadModel\CategoryTree;
use App\Application\Shop\UseCase\Command\Catalog\UpdateProductImageByAdmin\UpdateProductImageByAdminCommand;
use App\Application\Shop\UseCase\Command\Catalog\UpdateProductImageByAdmin\UpdateProductImageByAdminCommandHandler;
use App\Domain\SharedKernel\ValueObject\Slug;
use App\Domain\Shop\Catalog\Exception\CatalogDomainException;
use App\Domain\Shop\Catalog\Exception\CategoryNotFoundException;
use App\Domain\Shop\Catalog\Exception\ProductNotFoundException;
use App\Domain\Shop\Catalog\Model\Category;
use App\Domain\Shop\Catalog\Model\Product;
use App\Domain\Shop\Catalog\ValueObject\CategoryId;
use App\Domain\Shop\Catalog\ValueObject\CategoryTitle;
use App\Domain\Shop\Catalog\ValueObject\ProductDescription;
use App\Domain\Shop\Catalog\ValueObject\ProductId;
use App\Domain\Shop\Catalog\ValueObject\ProductSubtitle;
use App\Domain\Shop\Catalog\ValueObject\ProductTitle;
use App\Domain\Shop\Shared\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateProductImageByAdminTest extends TestCase
{
    private const string PRODUCT_ID = '550e8400-e29b-41d4-a716-446655440000';

    private const string CATEGORY_ID = '550e8400-e29b-41d4-a716-446655440001';

    private ProductRepositoryInterface&MockObject $productRepository;

    private CategoryRepositoryInterface&MockObject $categoryRepository;

    private TransactionalInterface&MockObject $transactional;

    private UpdateProductImageByAdminCommandHandler $handler;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->transactional = $this->createMock(TransactionalInterface::class);
        $this->handler = new UpdateProductImageByAdminCommandHandler(
            $this->productRepository,
            $this->categoryRepository,
            $this->transactional,
        );
    }

    public function testHandleUpdatesImageWhenProductExists(): void
    {
        $productId = ProductId::fromString(self::PRODUCT_ID);
        $product = $this->createProduct($productId);
        $categoryTree = $this->createCategoryTree($product->getCategoryId());
        $file = $this->createMock(FileInterface::class);
        $file->method('isValid')->willReturn(true);

        $command = new UpdateProductImageByAdminCommand(
            productId: $productId,
            imageFile: $file,
        );

        $this->productRepository->expects($this->once())
            ->method('updateImage')
            ->with($productId, $file)
            ->willReturn($product);

        $this->categoryRepository->expects($this->once())
            ->method('findTreeById')
            ->with($product->getCategoryId())
            ->willReturn($categoryTree);

        $this->transactional->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (callable $callback) {
                return $callback();
            });

        $output = $this->handler->handle($command);

        $this->assertSame($product, $output->productView->product);
        $this->assertSame($categoryTree, $output->productView->categoryTree);
    }

    public function testHandleThrowsExceptionWhenProductNotFound(): void
    {
        $productId = ProductId::fromString(self::PRODUCT_ID);
        $file = $this->createMock(FileInterface::class);
        $file->method('isValid')->willReturn(true);

        $command = new UpdateProductImageByAdminCommand(
            productId: $productId,
            imageFile: $file,
        );

        $this->productRepository->expects($this->once())
            ->method('updateImage')
            ->with($productId, $file)
            ->willReturn(null);

        $this->transactional->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (callable $callback) {
                return $callback();
            });

        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Product not found.');

        $this->handler->handle($command);
    }

    public function testHandleThrowsExceptionWhenCategoryNotFound(): void
    {
        $productId = ProductId::fromString(self::PRODUCT_ID);
        $product = $this->createProduct($productId);
        $file = $this->createMock(FileInterface::class);
        $file->method('isValid')->willReturn(true);

        $command = new UpdateProductImageByAdminCommand(
            productId: $productId,
            imageFile: $file,
        );

        $this->productRepository->expects($this->once())
            ->method('updateImage')
            ->with($productId, $file)
            ->willReturn($product);

        $this->categoryRepository->expects($this->once())
            ->method('findTreeById')
            ->with($product->getCategoryId())
            ->willReturn(null);

        $this->transactional->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (callable $callback) {
                return $callback();
            });

        $this->expectException(CategoryNotFoundException::class);
        $this->expectExceptionMessage('Category not found.');

        $this->handler->handle($command);
    }

    public function testHandleThrowsExceptionWhenImageFileIsInvalid(): void
    {
        $productId = ProductId::fromString(self::PRODUCT_ID);
        $file = $this->createMock(FileInterface::class);
        $file->method('isValid')->willReturn(false);

        $command = new UpdateProductImageByAdminCommand(
            productId: $productId,
            imageFile: $file,
        );

        $this->expectException(CatalogDomainException::class);
        $this->expectExceptionMessage('Invalid image file.');

        $this->handler->handle($command);
    }

    private function createProduct(ProductId $productId): Product
    {
        return Product::create(
            id: $productId,
            title: ProductTitle::fromString('Product title'),
            subtitle: ProductSubtitle::fromString('Product subtitle'),
            description: ProductDescription::fromString('Product description'),
            price: Money::fromInt(1299),
            slug: Slug::fromString('product-title'),
            categoryId: CategoryId::fromString(self::CATEGORY_ID),
            now: new DateTimeImmutable(),
        );
    }

    private function createCategoryTree(CategoryId $categoryId): CategoryTree
    {
        $category = Category::create(
            id: $categoryId,
            title: CategoryTitle::fromString('Category title'),
            slug: Slug::fromString('category-title'),
            now: new DateTimeImmutable(),
        );

        return new CategoryTree($category, null, []);
    }
}
