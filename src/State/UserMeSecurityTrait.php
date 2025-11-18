<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use App\Entity\User\User;
use App\Service\InfoCodes;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Trait providing security methods for User /me endpoints.
 *
 * Why throw a Security AccessDeniedException here (and not AccessDeniedHttpException)?
 * ------------------------------------------------------------------------------------
 * - Throwing a **Security** exception (AccessDeniedException from the Security component)
 *   is intercepted by Symfony's Security ExceptionListener, which triggers the firewall
 *   **entry point** (JWT) when the user is not authenticated. This yields a clean **401**
 *   challenge, exactly like your other secured endpoints.
 * - Throwing an **HTTP** exception (AccessDeniedHttpException) bypasses the Security layer
 *   entirely and immediately returns a **403**. In that case, the JWT authenticator is
 *   never engaged, so you don't get the uniform 401 behavior you expect when there's no/invalid token.
 *
 * Expected outcomes with this choice:
 * - No/empty/invalid token  -> entry point (JWT) starts -> **401 Unauthorized**
 * - Valid token but no rights -> Security denies -> **403 Forbidden**
 *
 * Note: We still keep `security: "is_granted('IS_AUTHENTICATED_FULLY')"` on the operation
 * for documentation/intent; this trait ensures correct pre-checks when the provider runs
 * before Api Platform's `security` expression is evaluated.
 */
trait UserMeSecurityTrait
{
    /**
     * Fournit l'instance Security aux classes utilisant ce trait.
     */
    abstract protected function getSecurity(): Security;

    /**
     * Get the current authenticated user or throw an exception.
     *
     * This method ensures that the user is authenticated before proceeding
     * with any business logic in /me endpoints.
     *
     * @return User The authenticated user
     *
     * @throws AccessDeniedException If no user is authenticated
     */
    protected function getCurrentUserOrThrow(): User
    {
        $user = $this->getSecurity()->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException(InfoCodes::USER['USER_AUTH_NOT_FOUND']);
        }

        return $user;
    }
}
