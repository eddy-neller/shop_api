<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\DisplayUser;

use App\Application\Shared\CQRS\Query\QueryInterface;
use App\Domain\User\ValueObject\UserId;

final class DisplayUserQuery implements QueryInterface
{
    public function __construct(
        public readonly UserId $userId,
    ) {
    }
}
