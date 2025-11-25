<?php

declare(strict_types=1);

namespace App\Presentation\User\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Shared\CQRS\Query\QueryBusInterface;
use App\Application\User\UseCase\Query\DisplayUser\DisplayUserOutput;
use App\Application\User\UseCase\Query\DisplayUser\DisplayUserQuery;
use App\Domain\User\ValueObject\UserId;
use App\Presentation\Shared\State\PresentationErrorCode;
use App\Presentation\User\Presenter\UserResourcePresenter;
use LogicException;

readonly class UserGetProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private UserResourcePresenter $userResourcePresenter,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if (!isset($uriVariables['id'])) {
            throw new LogicException(PresentationErrorCode::INVALID_INPUT->value);
        }

        $userId = UserId::fromString($uriVariables['id']);

        $query = new DisplayUserQuery($userId);

        /** @var DisplayUserOutput $output */
        $output = $this->queryBus->dispatch($query);

        return $this->userResourcePresenter->toResource($output->user);
    }
}
