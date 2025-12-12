<?php

namespace App\Domain\User\Security\ValueObject;

use InvalidArgumentException;

final class RoleSet
{
    public const string ROLE_USER = 'ROLE_USER';

    public const string ROLE_MODERATEUR = 'ROLE_MODERATEUR';

    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    public const string ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    private const array ALLOWED = [
        self::ROLE_USER,
        self::ROLE_MODERATEUR,
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
    ];

    /**
     * @var string[]
     */
    private array $roles;

    /**
     * @param string[] $roles
     */
    public function __construct(array $roles)
    {
        if ([] === $roles) {
            $roles = ['ROLE_USER'];
        }

        foreach ($roles as $role) {
            if (!is_string($role) || '' === trim($role)) {
                throw new InvalidArgumentException('Role invalide.');
            }

            if (!in_array($role, self::ALLOWED, true)) {
                throw new InvalidArgumentException('Role non autorisé: ' . $role);
            }
        }

        $this->roles = array_values(array_unique($roles));
    }

    public function all(): array
    {
        return $this->roles;
    }

    public function contains(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function add(string $role): self
    {
        $roles = $this->roles;
        $roles[] = $role;

        return new self($roles);
    }
}
