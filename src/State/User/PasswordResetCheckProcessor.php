<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\PasswordResetCheckInput;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use LogicException;

readonly class PasswordResetCheckProcessor implements ProcessorInterface
{
    public function __construct(
        private UserManager $userManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof PasswordResetCheckInput) {
            throw new LogicException(InfoCodes::INTERNAL['INVALID_INPUT']);
        }

        $this->userManager->assertValidResetPasswordToken($data->token);
    }
}
