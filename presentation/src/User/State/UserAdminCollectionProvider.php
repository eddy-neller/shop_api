<?php

declare(strict_types=1);

namespace App\Presentation\User\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\User\Port\AvatarUrlResolverInterface;
use App\Domain\User\Profile\ValueObject\Avatar;
use App\Infrastructure\Entity\User\User as DoctrineUser;
use App\Presentation\Shared\State\PaginatedCollectionProvider;
use App\Presentation\User\ApiResource\UserResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class UserAdminCollectionProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: PaginatedCollectionProvider::class)]
        private ProviderInterface $provider,
        private AvatarUrlResolverInterface $avatarUrlResolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $result = $this->provider->provide($operation, $uriVariables, $context);

        if (!is_iterable($result)) {
            return $result;
        }

        return array_map(function (mixed $item): UserResource {
            return $this->mapToResource($item);
        }, $result);
    }

    private function mapToResource(DoctrineUser $user): UserResource
    {
        $resource = new UserResource();

        $resource->id = $user->getId()->toString();
        $resource->firstname = $user->getFirstname();
        $resource->lastname = $user->getLastname();
        $resource->username = $user->getUsername();
        $resource->email = $user->getEmail();
        $resource->roles = $user->getRoles();
        $resource->status = ($user->getStatus() ?? 0);

        $avatar = new Avatar(fileName: $user->getAvatarName());
        $resource->avatarUrl = $this->avatarUrlResolver->resolve($avatar);

        $resource->lastVisit = $user->getLastVisit();
        $resource->nbLogin = $user->getNbLogin();
        $resource->createdAt = $user->getCreatedAt();
        $resource->updatedAt = $user->getUpdatedAt();

        return $resource;
    }
}
