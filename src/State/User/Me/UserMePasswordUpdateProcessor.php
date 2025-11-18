<?php

namespace App\State\User\Me;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\Me\UserMePasswordUpdateInput;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\UserMeSecurityTrait;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;

readonly class UserMePasswordUpdateProcessor implements ProcessorInterface
{
    use UserMeSecurityTrait;

    public function __construct(
        private Security $security,
        private UserManager $userManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserMePasswordUpdateInput) {
            throw new LogicException(InfoCodes::INTERNAL['INVALID_INPUT']);
        }

        $user = $this->getCurrentUserOrThrow();

        return $this->userManager->updatePassword($user, $data->newPassword);
    }

    protected function getSecurity(): Security
    {
        return $this->security;
    }
}
