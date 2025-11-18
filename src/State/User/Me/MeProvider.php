<?php

namespace App\State\User\Me;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User\User;
use App\State\UserMeSecurityTrait;
use Symfony\Bundle\SecurityBundle\Security;

readonly class MeProvider implements ProviderInterface
{
    use UserMeSecurityTrait;

    public function __construct(
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): User
    {
        return $this->getCurrentUserOrThrow();
    }

    protected function getSecurity(): Security
    {
        return $this->security;
    }
}
