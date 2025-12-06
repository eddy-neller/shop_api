<?php

namespace App\Infrastructure\Persistence\Doctrine\User;

use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\Firstname;
use App\Domain\User\Identity\ValueObject\Lastname;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Model\User as DomainUser;
use App\Domain\User\Preference\ValueObject\Preferences;
use App\Domain\User\Profile\ValueObject\Avatar;
use App\Domain\User\Security\ValueObject\HashedPassword;
use App\Domain\User\Security\ValueObject\RoleSet;
use App\Domain\User\Security\ValueObject\UserStatus;
use App\Infrastructure\Entity\User\User as DoctrineUser;
use Ramsey\Uuid\Uuid;

final class UserMapper
{
    public function toDomain(DoctrineUser $entity): DomainUser
    {
        $createdAt = $entity->getCreatedAt();
        $updatedAt = $entity->getUpdatedAt();
        $lastVisit = $entity->getLastVisit();

        $avatar = new Avatar(
            $entity->getAvatarName(),
            $entity->getAvatarUrl(),
            $entity->getAvatarUpdatedAt(),
        );

        return DomainUser::reconstitute(
            id: UserId::fromString($entity->getId()->toString()),
            username: new Username($entity->getUsername()),
            email: new EmailAddress($entity->getEmail()),
            password: new HashedPassword($entity->getPassword()),
            roles: new RoleSet($entity->getRoles()),
            status: UserStatus::fromInt($entity->getStatus()),
            security: $entity->getSecurity(),
            activeEmail: $entity->getActiveEmail(),
            resetPassword: $entity->getResetPassword(),
            preferences: Preferences::fromArray($entity->getPreferences() ?? []),
            avatar: $avatar,
            lastVisit: $lastVisit,
            loginCount: $entity->getNbLogin(),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            firstname: $entity->firstname ? new Firstname($entity->firstname) : null,
            lastname: $entity->lastname ? new Lastname($entity->lastname) : null,
        );
    }

    public function toDoctrine(DomainUser $user, ?DoctrineUser $entity = null): DoctrineUser
    {
        $entity = $entity ?? new DoctrineUser();

        if (null !== $user->getId()) {
            $entity->setId(Uuid::fromString($user->getId()->toString()));
        }

        $entity->setUsername($user->getUsername()->toString());
        $entity->firstname = $user->getFirstname()?->toString();
        $entity->lastname = $user->getLastname()?->toString();
        $entity->setEmail($user->getEmail()->toString());
        $entity->setPassword($user->getPassword()->toString());
        $entity->setRoles($user->getRoles()->all());
        $entity->setStatus($user->getStatus()->toInt());
        $entity->setSecurity($user->getSecurity());
        $entity->setActiveEmail($user->getActiveEmail());
        $entity->setResetPassword($user->getResetPassword());
        $entity->setPreferences($user->getPreferences()->toArray());

        $avatar = $user->getAvatar();
        $entity->setAvatarName($avatar->fileName());
        $entity->setAvatarUrl($avatar->url());
        $entity->setAvatarUpdatedAt($avatar->updatedAt());

        $entity->setLastVisit($user->getLastVisit());
        $entity->setNbLogin($user->getLoginCount());
        $entity->setCreatedAt($user->getCreatedAt());
        $entity->setUpdatedAt($user->getUpdatedAt());

        return $entity;
    }
}
