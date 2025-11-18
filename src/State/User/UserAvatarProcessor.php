<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\UserAvatarInput;
use App\Entity\User\User;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use LogicException;

/**
 * Processor pour gérer l'upload d'avatar d'un utilisateur par un admin.
 */
readonly class UserAvatarProcessor implements ProcessorInterface
{
    public function __construct(
        private UserManager $userManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (!$data instanceof UserAvatarInput) {
            throw new LogicException(InfoCodes::INTERNAL['INVALID_INPUT']);
        }

        $user = $this->userManager->getUserById($uriVariables['id']);

        return $this->userManager->updateAvatar($user, $data->avatarFile);
    }
}
