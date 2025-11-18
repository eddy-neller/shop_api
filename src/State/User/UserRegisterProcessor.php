<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\UserRegisterInput;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use LogicException;

readonly class UserRegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private UserManager $userManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserRegisterInput) {
            throw new LogicException(InfoCodes::INTERNAL['INVALID_INPUT']);
        }

        return $this->userManager->registerUser($data);
    }
}
