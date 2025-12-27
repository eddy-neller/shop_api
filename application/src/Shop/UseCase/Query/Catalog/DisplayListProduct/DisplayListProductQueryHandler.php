<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Query\Catalog\DisplayListProduct;

use App\Application\Shared\CQRS\Query\QueryHandlerInterface;
use App\Application\Shop\Port\ProductRepositoryInterface;

final readonly class DisplayListProductQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {
    }

    public function handle(DisplayListProductQuery $query): DisplayListProductOutput
    {
        $page = $query->pagination->page;
        $itemsPerPage = $query->pagination->itemsPerPage;
        $orderBy = [] !== $query->orderBy ? $query->orderBy : ['createdAt' => 'DESC'];

        $list = $this->repository->list(
            title: $query->title,
            subtitle: $query->subtitle,
            description: $query->description,
            orderBy: $orderBy,
            page: $page,
            itemsPerPage: $itemsPerPage,
        );

        return new DisplayListProductOutput(
            products: $list->products,
            totalItems: $list->totalItems,
            totalPages: $list->totalPages,
        );
    }
}
