<?php

namespace App\Security\Voter;

use App\Domain\User\ValueObject\RoleSet;
use App\Entity\Shop\Address;
use App\Infrastructure\Entity\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Les voters fonctionnent uniquement lorsqu'il s'agit d'items.
 * Trouvez un autre moyen pour les collections.
 */
class ShopAddressVoter extends Voter
{
    private const string ITEM_READ = 'shop_address:item:read';

    private const string ITEM_WRITE = 'shop_address:item:write';

    private const array GROUPS = [
        self::ITEM_READ,
        self::ITEM_WRITE,
    ];

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, self::GROUPS) && $subject instanceof Address;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        /* @var Address $subject */
        return match ($attribute) {
            self::ITEM_READ, self::ITEM_WRITE => $this->security->isGranted(RoleSet::ROLE_ADMIN) || $user->getId()->toString() === $subject->getUser()->getId()->toString(),
            default => false,
        };
    }
}
