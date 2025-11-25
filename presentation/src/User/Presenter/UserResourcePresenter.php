<?php

declare(strict_types=1);

namespace App\Presentation\User\Presenter;

use App\Application\User\Port\AvatarUrlResolverInterface;
use App\Domain\User\Model\User as DomainUser;
use App\Presentation\User\ApiResource\UserResource;

/**
 * Adapter/Presenter qui transforme un DomainUser en UserResource.
 */
final class UserResourcePresenter
{
    public function __construct(
        private readonly AvatarUrlResolverInterface $avatarUrlResolver,
    ) {
    }

    public function toResource(DomainUser $user): UserResource
    {
        $resource = new UserResource();

        $resource->id = $user->getId()?->toString() ?? '';
        $resource->firstname = $user->getFirstname()?->toString();
        $resource->lastname = $user->getLastname()?->toString();
        $resource->username = $user->getUsername()->toString();
        $resource->email = $user->getEmail()->toString();
        $resource->roles = $user->getRoles()->all();
        $resource->status = $user->getStatus()->toInt();

        $avatar = $user->getAvatar();
        $resource->avatarUrl = $this->avatarUrlResolver->resolve($avatar);

        $resource->lastVisit = $user->getLastVisit();
        $resource->nbLogin = $user->getLoginCount();
        $resource->createdAt = $user->getCreatedAt();
        $resource->updatedAt = $user->getUpdatedAt();

        return $resource;
    }
}
