<?php

namespace App\Service\User;

use App\Dto\User\UserPatchInput;
use App\Dto\User\UserPostInput;
use App\Dto\User\UserRegisterInput;
use App\Entity\User\Embedded\ActiveEmail;
use App\Entity\User\Embedded\ResetPassword;
use App\Entity\User\User;
use App\Enum\User\UserTokenScope;
use App\Repository\User\UserRepository;
use App\Service\BitField;
use App\Service\Encoder\CustomEncoder;
use App\Service\Helper\ObjectHelper;
use App\Service\InfoCodes;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    public const int MAX_RESET_MAIL = 3;

    public function __construct(
        private readonly ParameterBagInterface $bag,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly TokenManager $tokenManager,
        private readonly BitField $bitField,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserNotifier $userNotifier,
    ) {
    }

    /**
     * Récupère un user par son ID.
     *
     * @throws NotFoundHttpException si l'user n'existe pas
     */
    public function getUserById(string $userId): User
    {
        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            throw new NotFoundHttpException(InfoCodes::USER['USR_NOT_FOUND']);
        }

        return $user;
    }

    public function registerUser(UserRegisterInput $data): User
    {
        /** @var User $user */
        $user = (new ObjectHelper())->hydrateEntityFromDto($data, new User());

        $user->setRoles([User::ROLES['user']]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);

        $user->setPreferences((array) $data->preferences);

        $this->em->persist($user);
        $this->em->flush();

        $this->requestActivation($user);

        return $user;
    }

    /**
     * Crée un utilisateur par un administrateur.
     * L'admin définit directement les rôles, le statut et les informations de base.
     * Pas d'envoi d'email d'activation ni de gestion de souscription.
     */
    public function createUserByAdmin(UserPostInput $data): User
    {
        /** @var User $user */
        $user = (new ObjectHelper())->hydrateEntityFromDto($data, new User());

        $user->setRoles($data->roles);
        $user->setStatus($data->status);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Met à jour un utilisateur par un administrateur.
     * Tous les champs sont optionnels, seuls les champs fournis seront mis à jour.
     */
    public function updateUserByAdmin(string $userId, UserPatchInput $data): User
    {
        $user = $this->getUserById($userId);

        // Mettre à jour uniquement les champs fournis
        if (null !== $data->username) {
            $user->setUsername($data->username);
        }

        if (null !== $data->email) {
            $user->setEmail($data->email);
        }

        if (null !== $data->firstname) {
            $user->setFirstname($data->firstname);
        }

        if (null !== $data->lastname) {
            $user->setLastname($data->lastname);
        }

        if (null !== $data->roles) {
            $user->setRoles($data->roles);
        }

        if (null !== $data->status) {
            $user->setStatus($data->status);
        }

        // Mettre à jour le mot de passe uniquement si fourni
        if (null !== $data->password && '' !== trim($data->password)) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
            $user->setPassword($hashedPassword);
        }

        $this->em->flush();

        return $user;
    }

    public function requestActivation(User $user): void
    {
        if ($this->isActive($user)) {
            return;
        }

        $now = new DateTimeImmutable();
        $ttlSpec = $this->bag->get('register_token_ttl') ?? 'P2D';

        $activeEmail = $user->getActiveEmail();

        if ($activeEmail->mailSent >= self::MAX_RESET_MAIL) {
            return;
        }

        $newActiveEmail = new ActiveEmail(
            mailSent: $activeEmail->mailSent + 1,
            token: CustomEncoder::randomString(),
            tokenTtl: $now->add(new DateInterval($ttlSpec))->getTimestamp(),
        );

        $user->setActiveEmail($newActiveEmail);

        $this->userNotifier->sendRegisterActivationEmail($user);

        $this->em->flush();
    }

    public function validateActivation(string $rawToken): void
    {
        [$user, $token] = $this->resolveUserByToken($rawToken, scope: UserTokenScope::RegisterActivation);

        $activeEmail = $user->getActiveEmail();

        if (!hash_equals($activeEmail->token ?? '', $token)) {
            throw new BadRequestHttpException(InfoCodes::ACCOUNT_VALIDATION['EMAIL_TOKEN_INVALID']);
        }

        $ttl = (int) ($activeEmail->tokenTtl ?? 0);
        if ($ttl <= 0 || $ttl <= time()) {
            throw new BadRequestHttpException(InfoCodes::ACCOUNT_VALIDATION['EMAIL_TOKEN_EXPIRED']);
        }

        $user->setStatus($this->bitField->addValue($user->getStatus(), User::STATUS['ACTIVE']));
        $user->setActiveEmail(new ActiveEmail());

        $this->em->flush();
    }

    public function requestPasswordReset(User $user): void
    {
        $this->isLocked($user);

        $now = new DateTimeImmutable();
        $ttlSpec = $this->bag->get('reset_password_token_ttl') ?? 'PT15M';

        $resetPassword = $user->getResetPassword();

        if ($resetPassword->mailSent >= self::MAX_RESET_MAIL) {
            return;
        }

        $newResetPassword = new ResetPassword(
            mailSent: $resetPassword->mailSent + 1,
            token: CustomEncoder::randomString(),
            tokenTtl: $now->add(new DateInterval($ttlSpec))->getTimestamp(),
        );

        $user->setResetPassword($newResetPassword);

        $this->userNotifier->sendResetPasswordEmail($user);

        $this->em->flush();
    }

    public function assertValidResetPasswordToken(string $rawToken): User
    {
        [$user, $token] = $this->resolveUserByToken($rawToken, scope: UserTokenScope::ResetPassword);

        $resetPassword = $user->getResetPassword();

        if (!hash_equals($resetPassword->token ?? '', $token)) {
            throw new BadRequestHttpException(InfoCodes::RESET_PASSWORD['EMAIL_TOKEN_INVALID']);
        }

        $ttl = (int) ($resetPassword->tokenTtl ?? 0);
        if ($ttl <= 0 || $ttl <= time()) {
            throw new BadRequestHttpException(InfoCodes::RESET_PASSWORD['EMAIL_TOKEN_EXPIRED']);
        }

        return $user;
    }

    public function validateResetPassword(string $rawToken, string $plainPassword): void
    {
        $user = $this->assertValidResetPasswordToken($rawToken);

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setResetPassword(new ResetPassword());

        $this->em->flush();
    }

    private function resolveUserByToken(string $rawToken, UserTokenScope $scope): array
    {
        $split = $this->tokenManager->splitToken($rawToken);
        $email = (string) ($split['email'] ?? '');
        $token = (string) ($split['token'] ?? '');

        if (empty($email) || empty($token)) {
            $code = 'resetPassword' === $scope->value
                ? InfoCodes::RESET_PASSWORD['EMAIL_TOKEN_INVALID']
                : InfoCodes::ACCOUNT_VALIDATION['EMAIL_TOKEN_INVALID'];
            throw new BadRequestHttpException($code);
        }

        /** @var User|null $user */
        $user = $this->tokenManager->retrieveUser($scope->value, 'token', $token);

        if (!$user instanceof User || $user->getEmail() !== $email) {
            $code = 'resetPassword' === $scope->value
                ? InfoCodes::RESET_PASSWORD['USER_NOT_FOUND_WITH_TOKEN']
                : InfoCodes::ACCOUNT_VALIDATION['USER_NOT_FOUND_WITH_TOKEN'];
            throw new BadRequestHttpException($code);
        }

        $this->isLocked($user);

        return [$user, $token];
    }

    private function isActive(User $user): bool
    {
        return $this->bitField->checkValue($user->getStatus(), User::STATUS['ACTIVE']);
    }

    private function isLocked(User $user): void
    {
        if ($this->bitField->checkValue($user->getStatus(), User::STATUS['BLOCKED'])) {
            throw new BadRequestHttpException(InfoCodes::USER['LOCKED_ACCOUNT']);
        }
    }

    /**
     * Met à jour l'avatar de l'utilisateur.
     */
    public function updateAvatar(User $user, File $avatarFile): User
    {
        $user->setAvatarFile($avatarFile);
        $this->em->flush();

        return $user;
    }

    /**
     * Met à jour le mot de passe de l'utilisateur.
     */
    public function updatePassword(User $user, string $plainPassword): User
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->em->flush();

        return $user;
    }
}
