<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\UserPatchInput;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use LogicException;

readonly class UserPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private UserManager $userManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserPatchInput) {
            throw new LogicException(InfoCodes::INTERNAL['INVALID_INPUT']);
        }

        return $this->userManager->updateUserByAdmin($uriVariables['id'], $data);
    }
}
