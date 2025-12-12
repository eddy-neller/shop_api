<?php

declare(strict_types=1);

namespace App\Presentation\User\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Shared\CQRS\Command\CommandBusInterface;
use App\Application\User\UseCase\Command\UploadAndUpdateAvatar\UploadAndUpdateAvatarCommand;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Presentation\Shared\Adapter\SymfonyFileAdapter;
use App\Presentation\Shared\State\PresentationErrorCode;
use App\Presentation\User\Dto\UserAvatarInput;
use App\Presentation\User\Presenter\UserResourcePresenter;
use LogicException;

/**
 * Processor pour gérer l'upload d'avatar d'un utilisateur par un admin.
 */
readonly class UserAvatarProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private UserResourcePresenter $userResourcePresenter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if (!$data instanceof UserAvatarInput) {
            throw new LogicException(PresentationErrorCode::INVALID_INPUT->value);
        }

        $userId = UserId::fromString($uriVariables['id']);

        $fileAdapter = new SymfonyFileAdapter($data->avatarFile);

        $command = new UploadAndUpdateAvatarCommand(
            userId: $userId,
            avatarFile: $fileAdapter,
        );

        $output = $this->commandBus->dispatch($command);

        return $this->userResourcePresenter->toResource($output->user);
    }
}
