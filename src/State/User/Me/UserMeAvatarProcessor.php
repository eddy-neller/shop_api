<?php

namespace App\State\User\Me;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\Me\UserMeAvatarInput;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\UserMeSecurityTrait;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;

readonly class UserMeAvatarProcessor implements ProcessorInterface
{
    use UserMeSecurityTrait;

    public function __construct(
        private Security $security,
        private UserManager $userManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserMeAvatarInput) {
            throw new LogicException(InfoCodes::INTERNAL['INVALID_INPUT']);
        }

        $user = $this->getCurrentUserOrThrow();

        return $this->userManager->updateAvatar($user, $data->avatarFile);
    }

    protected function getSecurity(): Security
    {
        return $this->security;
    }
}
