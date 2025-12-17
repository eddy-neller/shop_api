<?php

declare(strict_types=1);

namespace App\Application\Shop\UseCase\Command\Catalog\UpdateCategoryByAdmin;

use App\Application\Shared\CQRS\Command\CommandInterface;
use App\Domain\Shop\Catalog\ValueObject\CategoryId;

final readonly class UpdateCategoryByAdminCommand implements CommandInterface
{
    public function __construct(
        public CategoryId $categoryId,
        public ?string $title,
        public ?string $description,
        public ?CategoryId $parentId,
    ) {
    }
}
