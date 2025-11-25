<?php

namespace App\Presentation\User\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Shared\CQRS\Command\CommandBusInterface;
use App\Application\User\UseCase\Command\DeleteUserByAdmin\DeleteUserByAdminCommand;
use App\Domain\User\ValueObject\UserId;
use App\Presentation\Shared\State\PresentationErrorCode;
use LogicException;

readonly class UserDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!isset($uriVariables['id'])) {
            throw new LogicException(PresentationErrorCode::INVALID_INPUT->value);
        }

        $userId = UserId::fromString($uriVariables['id']);

        $command = new DeleteUserByAdminCommand(
            userId: $userId,
        );
        $this->commandBus->dispatch($command);

        return null;
    }
}
