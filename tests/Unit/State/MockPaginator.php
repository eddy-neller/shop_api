<?php

namespace App\Tests\Unit\State;

use ApiPlatform\State\Pagination\PaginatorInterface;

class MockPaginator implements PaginatorInterface, \IteratorAggregate
{
    public function __construct(
        private array $items,
        private float $totalItems,
        private float $lastPage,
        private float $currentPage = 1.0,
        private float $itemsPerPage = 10.0,
    ) {
    }

    public function getTotalItems(): float
    {
        return $this->totalItems;
    }

    public function getLastPage(): float
    {
        return $this->lastPage;
    }

    public function getCurrentPage(): float
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): float
    {
        return $this->itemsPerPage;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
