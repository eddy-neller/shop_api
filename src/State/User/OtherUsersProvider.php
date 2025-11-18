<?php

declare(strict_types=1);

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\State\PaginatedCollectionProvider;
use App\State\UserMeSecurityTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provider pour récupérer tous les utilisateurs sauf l'utilisateur authentifié.
 * Combine la sécurité (UserMeSecurityTrait), la pagination (PaginatedCollectionProvider)
 * et le filtrage de l'utilisateur courant via le filtre NotInFilter.
 */
readonly class OtherUsersProvider implements ProviderInterface
{
    use UserMeSecurityTrait;

    public function __construct(
        #[Autowire(service: PaginatedCollectionProvider::class)]
        private ProviderInterface $paginatedProvider,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $currentUser = $this->getCurrentUserOrThrow();

        // Ajouter le filtre exclude_id dans le contexte pour que NotInFilter le traite
        $context['filters'] = array_merge($context['filters'] ?? [], [
            'exclude_id' => [$currentUser->getId()->toString()],
        ]);

        // Définir le tri par défaut sur username si aucun tri n'est spécifié
        if (!isset($context['filters']['order'])) {
            $context['filters']['order'] = [];
        }

        if (!isset($context['filters']['order']['username'])) {
            $context['filters']['order']['username'] = 'ASC';
        }

        // Déléguer au provider paginé qui gérera ensuite le provider Doctrine ORM
        return $this->paginatedProvider->provide($operation, $uriVariables, $context);
    }

    protected function getSecurity(): Security
    {
        return $this->security;
    }
}
