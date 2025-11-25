<?php

namespace App\Domain\User\Model;

use App\Domain\SharedKernel\Event\DomainEventTrait;
use App\Domain\User\Event\ActivationEmailRequestedEvent;
use App\Domain\User\Event\PasswordResetCompletedEvent;
use App\Domain\User\Event\PasswordResetRequestedEvent;
use App\Domain\User\Event\UserActivatedEvent;
use App\Domain\User\Event\UserCreatedByAdminEvent;
use App\Domain\User\Event\UserDeletedEvent;
use App\Domain\User\Event\UserRegisteredEvent;
use App\Domain\User\Event\UserUpdatedByAdminEvent;
use App\Domain\User\Exception\ActivationLimitReachedException;
use App\Domain\User\Exception\ResetPasswordLimitReachedException;
use App\Domain\User\Exception\UserLockedException;
use App\Domain\User\ValueObject\ActiveEmail;
use App\Domain\User\ValueObject\Avatar;
use App\Domain\User\ValueObject\EmailAddress;
use App\Domain\User\ValueObject\Firstname;
use App\Domain\User\ValueObject\HashedPassword;
use App\Domain\User\ValueObject\Lastname;
use App\Domain\User\ValueObject\Preferences;
use App\Domain\User\ValueObject\ResetPassword;
use App\Domain\User\ValueObject\RoleSet;
use App\Domain\User\ValueObject\Security;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;
use App\Domain\User\ValueObject\UserStatus;
use DateTimeImmutable;

final class User
{
    use DomainEventTrait;

    private const int MAX_TOKEN_REQUESTS = 3;

    private function __construct(
        private readonly ?UserId $id,
        private Username $username,
        private ?Firstname $firstname,
        private ?Lastname $lastname,
        private EmailAddress $email,
        private HashedPassword $password,
        private RoleSet $roles,
        private UserStatus $status,
        private Security $security,
        private ActiveEmail $activeEmail,
        private ResetPassword $resetPassword,
        private Preferences $preferences,
        private Avatar $avatar,
        private DateTimeImmutable $lastVisit,
        private int $loginCount,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }

    public static function register(
        UserId $id,
        Username $username,
        EmailAddress $email,
        HashedPassword $password,
        Preferences $preferences,
        DateTimeImmutable $now,
        ?Firstname $firstname = null,
        ?Lastname $lastname = null,
    ): self {
        $user = new self(
            id: $id,
            username: $username,
            firstname: $firstname,
            lastname: $lastname,
            email: $email,
            password: $password,
            roles: new RoleSet(['ROLE_USER']),
            status: UserStatus::inactive(),
            security: new Security(),
            activeEmail: new ActiveEmail(),
            resetPassword: new ResetPassword(),
            preferences: $preferences,
            avatar: new Avatar(),
            lastVisit: $now,
            loginCount: 0,
            createdAt: $now,
            updatedAt: $now,
        );

        $user->recordEvent(new UserRegisteredEvent(
            userId: $id,
            email: $email,
            occurredOn: $now,
        ));

        return $user;
    }

    public static function createByAdmin(
        UserId $id,
        Username $username,
        EmailAddress $email,
        HashedPassword $password,
        RoleSet $roles,
        UserStatus $status,
        DateTimeImmutable $now,
        ?Firstname $firstname = null,
        ?Lastname $lastname = null,
        ?Preferences $preferences = null,
    ): self {
        $user = new self(
            id: $id,
            username: $username,
            firstname: $firstname,
            lastname: $lastname,
            email: $email,
            password: $password,
            roles: $roles,
            status: $status,
            security: new Security(),
            activeEmail: new ActiveEmail(),
            resetPassword: new ResetPassword(),
            preferences: $preferences ?? new Preferences(),
            avatar: new Avatar(),
            lastVisit: $now,
            loginCount: 0,
            createdAt: $now,
            updatedAt: $now,
        );

        $user->recordEvent(new UserCreatedByAdminEvent(
            userId: $id,
            email: $email,
            occurredOn: $now,
        ));

        return $user;
    }

    public static function reconstitute(
        UserId $id,
        Username $username,
        EmailAddress $email,
        HashedPassword $password,
        RoleSet $roles,
        UserStatus $status,
        Security $security,
        ActiveEmail $activeEmail,
        ResetPassword $resetPassword,
        Preferences $preferences,
        Avatar $avatar,
        DateTimeImmutable $lastVisit,
        int $loginCount,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?Firstname $firstname = null,
        ?Lastname $lastname = null,
    ): self {
        return new self(
            id: $id,
            username: $username,
            firstname: $firstname,
            lastname: $lastname,
            email: $email,
            password: $password,
            roles: $roles,
            status: $status,
            security: $security,
            activeEmail: $activeEmail,
            resetPassword: $resetPassword,
            preferences: $preferences,
            avatar: $avatar,
            lastVisit: $lastVisit,
            loginCount: $loginCount,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function requestActivation(string $token, DateTimeImmutable $expiresAt, DateTimeImmutable $now): void
    {
        if ($this->getActiveEmail()->getMailSent() >= self::MAX_TOKEN_REQUESTS) {
            throw new ActivationLimitReachedException();
        }

        $this->setActiveEmail(new ActiveEmail(
            mailSent: $this->getActiveEmail()->getMailSent() + 1,
            token: $token,
            tokenTtl: $expiresAt->getTimestamp(),
            lastAttempt: $now,
        ));

        if (null !== $this->id) {
            $this->recordEvent(new ActivationEmailRequestedEvent(
                userId: $this->id,
                email: $this->email,
                occurredOn: $now,
            ));
        }
    }

    public function clearActivation(): void
    {
        $this->setActiveEmail(new ActiveEmail());
    }

    public function activate(DateTimeImmutable $now): void
    {
        $this->setStatus($this->getStatus()->addFlag(UserStatus::ACTIVE));
        $this->clearActivation();
        $this->setUpdatedAt($now);

        if (null !== $this->id) {
            $this->recordEvent(new UserActivatedEvent(
                userId: $this->id,
                occurredOn: $now,
            ));
        }
    }

    public function requestPasswordReset(string $token, DateTimeImmutable $expiresAt, DateTimeImmutable $now): void
    {
        $this->assertNotLocked();

        if ($this->getResetPassword()->getMailSent() >= self::MAX_TOKEN_REQUESTS) {
            throw new ResetPasswordLimitReachedException();
        }

        $this->setResetPassword(new ResetPassword(
            mailSent: $this->getResetPassword()->getMailSent() + 1,
            token: $token,
            tokenTtl: $expiresAt->getTimestamp(),
        ));

        if (null !== $this->id) {
            $this->recordEvent(new PasswordResetRequestedEvent(
                userId: $this->id,
                email: $this->email,
                occurredOn: $now,
            ));
        }
    }

    public function completePasswordReset(HashedPassword $password, DateTimeImmutable $now): void
    {
        $this->setPassword($password);
        $this->setResetPassword(new ResetPassword());
        $this->setUpdatedAt($now);

        if (null !== $this->id) {
            $this->recordEvent(new PasswordResetCompletedEvent(
                userId: $this->id,
                occurredOn: $now,
            ));
        }
    }

    public function delete(DateTimeImmutable $now): void
    {
        if (null !== $this->id) {
            $this->recordEvent(new UserDeletedEvent(
                userId: $this->id,
                occurredOn: $now,
            ));
        }
    }

    public function changePassword(HashedPassword $password, DateTimeImmutable $now): void
    {
        $this->setPassword($password);
        $this->setUpdatedAt($now);
    }

    public function updateAvatar(Avatar $avatar, DateTimeImmutable $now): void
    {
        $this->setAvatar($avatar);
        $this->setUpdatedAt($now);
    }

    public function updateByAdmin(
        DateTimeImmutable $now,
        ?Username $username = null,
        ?EmailAddress $email = null,
        ?Firstname $firstname = null,
        ?Lastname $lastname = null,
        ?RoleSet $roles = null,
        ?UserStatus $status = null,
        ?HashedPassword $password = null,
    ): void {
        $hasChanges = false;

        if (null !== $username) {
            $this->setUsername($username);
            $hasChanges = true;
        }

        if (null !== $email) {
            $this->setEmail($email);
            $hasChanges = true;
        }

        if (null !== $firstname) {
            $this->setFirstname($firstname);
            $hasChanges = true;
        }

        if (null !== $lastname) {
            $this->setLastname($lastname);
            $hasChanges = true;
        }

        if (null !== $roles) {
            $this->setRoles($roles);
            $hasChanges = true;
        }

        if (null !== $status) {
            $this->setStatus($status);
            $hasChanges = true;
        }

        if (null !== $password) {
            $this->setPassword($password);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $this->setUpdatedAt($now);

            if (null !== $this->id) {
                $this->recordEvent(new UserUpdatedByAdminEvent(
                    userId: $this->id,
                    occurredOn: $now,
                ));
            }
        }
    }

    public function isActive(): bool
    {
        return $this->getStatus()->hasFlag(UserStatus::ACTIVE);
    }

    public function isLocked(): bool
    {
        return $this->getStatus()->hasFlag(UserStatus::BLOCKED);
    }

    public function getId(): ?UserId
    {
        return $this->id;
    }

    public function getUsername(): Username
    {
        return $this->username;
    }

    public function getFirstname(): ?Firstname
    {
        return $this->firstname;
    }

    public function getLastname(): ?Lastname
    {
        return $this->lastname;
    }

    public function getEmail(): EmailAddress
    {
        return $this->email;
    }

    public function getPassword(): HashedPassword
    {
        return $this->password;
    }

    public function getRoles(): RoleSet
    {
        return $this->roles;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function getActiveEmail(): ActiveEmail
    {
        return $this->activeEmail;
    }

    public function getResetPassword(): ResetPassword
    {
        return $this->resetPassword;
    }

    public function getPreferences(): Preferences
    {
        return $this->preferences;
    }

    public function getAvatar(): Avatar
    {
        return $this->avatar;
    }

    public function getLastVisit(): DateTimeImmutable
    {
        return $this->lastVisit;
    }

    public function getLoginCount(): int
    {
        return $this->loginCount;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function setUsername(Username $username): void
    {
        $this->username = $username;
    }

    private function setFirstname(?Firstname $firstname): void
    {
        $this->firstname = $firstname;
    }

    private function setLastname(?Lastname $lastname): void
    {
        $this->lastname = $lastname;
    }

    private function setEmail(EmailAddress $email): void
    {
        $this->email = $email;
    }

    private function setPassword(HashedPassword $password): void
    {
        $this->password = $password;
    }

    private function setRoles(RoleSet $roles): void
    {
        $this->roles = $roles;
    }

    private function setStatus(UserStatus $status): void
    {
        $this->status = $status;
    }

    private function setActiveEmail(ActiveEmail $activeEmail): void
    {
        $this->activeEmail = $activeEmail;
    }

    private function setResetPassword(ResetPassword $resetPassword): void
    {
        $this->resetPassword = $resetPassword;
    }

    private function setAvatar(Avatar $avatar): void
    {
        $this->avatar = $avatar;
    }

    private function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    private function assertNotLocked(): void
    {
        if ($this->isLocked()) {
            throw new UserLockedException();
        }
    }
}
