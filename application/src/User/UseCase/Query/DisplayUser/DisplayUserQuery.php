<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\DisplayUser;

use App\Application\Shared\CQRS\Query\QueryInterface;
use App\Domain\User\Identity\ValueObject\UserId;

final readonly class DisplayUserQuery implements QueryInterface
{
    public function __construct(
        public UserId $userId,
    ) {
    }
}
