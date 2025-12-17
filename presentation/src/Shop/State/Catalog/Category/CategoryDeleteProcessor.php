<?php

declare(strict_types=1);

namespace App\Presentation\Shop\State\Catalog\Category;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Shared\CQRS\Command\CommandBusInterface;
use App\Application\Shop\UseCase\Command\Catalog\DeleteCategoryByAdmin\DeleteCategoryByAdminCommand;
use App\Domain\Shop\Catalog\ValueObject\CategoryId;
use App\Presentation\Shared\State\PresentationErrorCode;
use LogicException;

final readonly class CategoryDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        if (!isset($uriVariables['id']) || !is_string($uriVariables['id'])) {
            throw new LogicException(PresentationErrorCode::INVALID_INPUT->value);
        }

        $categoryId = CategoryId::fromString($uriVariables['id']);
        $this->commandBus->dispatch(new DeleteCategoryByAdminCommand($categoryId));

        return null;
    }
}
