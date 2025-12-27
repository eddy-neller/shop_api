<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\DisplayListUser;

use App\Application\Shared\CQRS\Query\QueryHandlerInterface;
use App\Application\User\Port\UserRepositoryInterface;

final readonly class DisplayListUserQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {
    }

    public function handle(DisplayListUserQuery $query): DisplayListUserOutput
    {
        $page = $query->pagination->page;
        $itemsPerPage = $query->pagination->itemsPerPage;
        $orderBy = [] !== $query->orderBy ? $query->orderBy : ['createdAt' => 'DESC'];

        $list = $this->repository->list(
            username: $query->username,
            email: $query->email,
            orderBy: $orderBy,
            page: $page,
            itemsPerPage: $itemsPerPage,
        );

        return new DisplayListUserOutput(
            users: $list->users,
            totalItems: $list->totalItems,
            totalPages: $list->totalPages,
        );
    }
}
