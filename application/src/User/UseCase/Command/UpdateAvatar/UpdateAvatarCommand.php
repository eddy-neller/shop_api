<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Command\UpdateAvatar;

use App\Application\Shared\CQRS\Command\CommandInterface;
use App\Domain\User\Identity\ValueObject\UserId;

final readonly class UpdateAvatarCommand implements CommandInterface
{
    public function __construct(
        public UserId $userId,
        public ?string $avatarFileName,
        public ?string $avatarUrl = null,
    ) {
    }
}
