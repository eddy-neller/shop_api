<?php

declare(strict_types=1);

namespace App\Presentation\Shop\State\Catalog\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Shared\CQRS\Query\QueryBusInterface;
use App\Application\Shared\ReadModel\Pagination;
use App\Application\Shop\UseCase\Query\Catalog\DisplayListProduct\DisplayListProductQuery;
use App\Presentation\Shop\Presenter\Catalog\ProductResourcePresenter;
use Symfony\Component\HttpFoundation\Request;

final readonly class ProductCollectionProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private ProductResourcePresenter $productResourcePresenter,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];
        if (!is_array($filters)) {
            $filters = [];
        }

        $pagination = Pagination::fromRaw($filters['page'] ?? null, $filters['itemsPerPage'] ?? null);
        $title = is_string($filters['title'] ?? null) ? $filters['title'] : null;
        $subtitle = is_string($filters['subtitle'] ?? null) ? $filters['subtitle'] : null;
        $description = is_string($filters['description'] ?? null) ? $filters['description'] : null;
        $orderBy = is_array($filters['order'] ?? null) ? $filters['order'] : [];

        $output = $this->queryBus->dispatch(new DisplayListProductQuery(
            pagination: $pagination,
            title: $title,
            subtitle: $subtitle,
            description: $description,
            orderBy: $orderBy,
        ));

        $request = $context['request'] ?? null;
        if ($request instanceof Request) {
            $request->attributes->set('_total_items', $output->totalItems);
            $request->attributes->set('_total_pages', $output->totalPages);
        }

        $items = [];
        foreach ($output->products as $product) {
            $items[] = $this->productResourcePresenter->toResource($product);
        }

        return $items;
    }
}
