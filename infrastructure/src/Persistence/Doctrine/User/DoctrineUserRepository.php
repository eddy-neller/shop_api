<?php

namespace App\Infrastructure\Persistence\Doctrine\User;

use App\Application\Shared\Port\EventDispatcherInterface;
use App\Application\Shared\Port\UuidGeneratorInterface;
use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Model\User as DomainUser;
use App\Infrastructure\Entity\User\User as DoctrineUser;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $repository,
        private readonly UserMapper $mapper,
        private readonly UuidGeneratorInterface $uuidGenerator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function nextIdentity(): UserId
    {
        return UserId::fromString($this->uuidGenerator->generate());
    }

    public function save(DomainUser $user): void
    {
        $entity = $this->findEntity($user->getId());
        $entity = $this->mapper->toDoctrine($user, $entity);

        $this->em->persist($entity);
        $this->em->flush();

        $this->dispatchDomainEvents($user);
    }

    public function delete(DomainUser $user): void
    {
        $id = $user->getId();
        if (null === $id) {
            return;
        }

        $entity = $this->repository->find($id->toString());
        if (null !== $entity) {
            $this->em->remove($entity);
            $this->em->flush();
        }

        $this->dispatchDomainEvents($user);
    }

    public function findById(UserId $id): ?DomainUser
    {
        $entity = $this->repository->find($id->toString());

        return $entity ? $this->mapper->toDomain($entity) : null;
    }

    public function findByEmail(EmailAddress $email): ?DomainUser
    {
        $entity = $this->repository->findOneBy(['email' => $email->toString()]);

        return $entity ? $this->mapper->toDomain($entity) : null;
    }

    public function findByActivationToken(string $token): ?DomainUser
    {
        $entity = $this->repository->findInJsonField('activeEmail', 'token', $token);

        return $entity ? $this->mapper->toDomain($entity) : null;
    }

    public function findByResetPasswordToken(string $token): ?DomainUser
    {
        $entity = $this->repository->findInJsonField('resetPassword', 'token', $token);

        return $entity ? $this->mapper->toDomain($entity) : null;
    }

    public function findByUsername(Username $username): ?DomainUser
    {
        $entity = $this->repository->findOneBy(['username' => $username->toString()]);

        return $entity ? $this->mapper->toDomain($entity) : null;
    }

    /**
     * @return DomainUser[]
     */
    public function findAll(): array
    {
        $entities = $this->repository->findAll();

        return array_map(
            fn (DoctrineUser $entity) => $this->mapper->toDomain($entity),
            $entities
        );
    }

    private function dispatchDomainEvents(DomainUser $user): void
    {
        $events = $user->getDomainEvents();
        if (!empty($events)) {
            $this->eventDispatcher->dispatchAll($events);
            $user->clearDomainEvents();
        }
    }

    private function findEntity(?UserId $id): ?DoctrineUser
    {
        if (null === $id) {
            return null;
        }

        return $this->repository->find($id->toString());
    }
}
