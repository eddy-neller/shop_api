<?php

namespace App\Entity\User;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Dto\User\Me\UserMeAvatarInput;
use App\Dto\User\Me\UserMePasswordUpdateInput;
use App\Dto\User\PasswordResetCheckInput;
use App\Dto\User\PasswordResetConfirmInput;
use App\Dto\User\PasswordResetRequestInput;
use App\Dto\User\UserActivationRequestInput;
use App\Dto\User\UserActivationValidationInput;
use App\Dto\User\UserAvatarInput;
use App\Dto\User\UserPatchInput;
use App\Dto\User\UserPostInput;
use App\Dto\User\UserRegisterInput;
use App\Entity\Shop\Address;
use App\Entity\Shop\Order;
use App\Entity\User\Embedded\ActiveEmail;
use App\Entity\User\Embedded\ResetPassword;
use App\Entity\User\Embedded\Security;
use App\Repository\User\UserRepository;
use App\Service\RouteRequirements;
use App\State\PaginatedCollectionProvider;
use App\State\User\Me\MeProvider;
use App\State\User\Me\UserMeAvatarProcessor;
use App\State\User\Me\UserMePasswordUpdateProcessor;
use App\State\User\OtherUsersProvider;
use App\State\User\PasswordResetCheckProcessor;
use App\State\User\PasswordResetConfirmProcessor;
use App\State\User\PasswordResetRequestProcessor;
use App\State\User\UserActivationRequestProcessor;
use App\State\User\UserActivationValidationProcessor;
use App\State\User\UserAvatarProcessor;
use App\State\User\UserPatchProcessor;
use App\State\User\UserPostProcessor;
use App\State\User\UserRegisterProcessor;
use ArrayObject;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Table(name: '`user`')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new Get(
            requirements: ['id' => RouteRequirements::UUID],
            name: self::PREFIX_NAME . 'get',
        ),
        new Get(
            uriTemplate: '/users/me',
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::PREFIX_NAME . 'me',
            provider: MeProvider::class
        ),
        new Patch(
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                summary: 'Update a user (Admin only).',
                description: 'Update a user. All fields are optional. This endpoint is accessible only by administrators.',
                requestBody: new RequestBody(
                    description: 'User update request body',
                    required: true
                ),
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            input: UserPatchInput::class,
            name: self::PREFIX_NAME . 'patch',
            processor: UserPatchProcessor::class,
        ),
        new Delete(
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            name: self::PREFIX_NAME . 'delete',
        ),
        new Post(
            uriTemplate: '/users/me/avatar',
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Model\Operation(
                summary: 'Update my avatar image',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'avatarFile' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                        'description' => 'My avatar image',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ),
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            input: UserMeAvatarInput::class,
            name: self::PREFIX_NAME . 'me-avatar',
            processor: UserMeAvatarProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/{id}/avatar',
            inputFormats: ['multipart' => ['multipart/form-data']],
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                summary: 'Upload avatar for a user (admin only)',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'avatarFile' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                        'description' => 'Avatar image (max 2MB, max 512×512px)',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ),
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            input: UserAvatarInput::class,
            name: self::PREFIX_NAME . 'avatar',
            processor: UserAvatarProcessor::class,
        ),
        new Patch(
            uriTemplate: '/users/me/update-password',
            status: 204,
            openapi: new Model\Operation(
                summary: 'Update my password.',
                description: 'Update my password.',
                requestBody: new RequestBody(
                    description: 'Update my password request body',
                    required: true
                ),
                security: [['ApiKeyAuth' => []]],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            input: UserMePasswordUpdateInput::class,
            output: false,
            name: self::PREFIX_NAME . 'me-update-password',
            processor: UserMePasswordUpdateProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/users/me/other-users',
            openapi: new Model\Operation(
                summary: 'Get other users.',
                description: 'Get other users.',
                security: [['ApiKeyAuth' => []]],
            ),
            paginationEnabled: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::PREFIX_NAME . 'me-other-users',
            provider: OtherUsersProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/users',
            openapi: new Model\Operation(
                summary: 'Get all users (Admin only).',
                description: 'Get all users. This endpoint is accessible only by administrators.',
                security: [['ApiKeyAuth' => []]],
            ),
            paginationClientItemsPerPage: true,
            security: "is_granted('" . User::ROLES['admin'] . "')",
            name: self::PREFIX_NAME . 'admin-col',
            provider: PaginatedCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/users',
            openapi: new Model\Operation(
                summary: 'Create a new user (Admin only).',
                description: 'Create a new user. This endpoint is accessible only by administrators.',
                requestBody: new RequestBody(
                    description: 'User creation request body',
                    required: true
                ),
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            input: UserPostInput::class,
            name: self::PREFIX_NAME . 'admin-create',
            processor: UserPostProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/register',
            input: UserRegisterInput::class,
            name: self::PREFIX_NAME . 'register',
            processor: UserRegisterProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/register/email-activation-request',
            status: 204,
            openapi: new Model\Operation(
                summary: 'Allows you to request an activation email.',
                description: 'Sends an activation email to the user with a token to activate their account.',
                requestBody: new RequestBody(
                    description: 'Email activation request request body',
                    required: true
                ),
            ),
            input: UserActivationRequestInput::class,
            output: false,
            name: self::PREFIX_NAME . 'register-resend',
            processor: UserActivationRequestProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/register/validation',
            status: 204,
            openapi: new Model\Operation(
                summary: 'Validates the registration of the account created.',
                description: 'Validates an email-based user registration using the provided token.',
                requestBody: new RequestBody(
                    description: 'Register validation request body',
                    required: true
                ),
            ),
            input: UserActivationValidationInput::class,
            output: false,
            name: self::PREFIX_NAME . 'register-validation',
            processor: UserActivationValidationProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/reset-password/request',
            status: 204,
            openapi: new Model\Operation(
                summary: 'Request a password reset email.',
                description: 'Sends an activation email to the user with a token to change the password.',
                requestBody: new RequestBody(
                    description: 'Password reset request request body',
                    required: true
                ),
            ),
            input: PasswordResetRequestInput::class,
            output: false,
            name: self::PREFIX_NAME . 'password-reset-request',
            processor: PasswordResetRequestProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/reset-password/check',
            status: 204,
            openapi: new Model\Operation(
                summary: 'Check a password reset token.',
                description: 'Check a password reset token.',
                requestBody: new RequestBody(
                    description: 'Password reset check request body',
                    required: true
                ),
            ),
            input: PasswordResetCheckInput::class,
            output: false,
            name: self::PREFIX_NAME . 'password-reset-check',
            processor: PasswordResetCheckProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/reset-password/confirm',
            status: 204,
            openapi: new Model\Operation(
                summary: 'Confirm password reset with a new password.',
                description: 'Confirm password reset with a new password.',
                requestBody: new RequestBody(
                    description: 'Password reset confirm request body',
                    required: true
                ),
            ),
            input: PasswordResetConfirmInput::class,
            output: false,
            name: self::PREFIX_NAME . 'password-reset-confirm',
            processor: PasswordResetConfirmProcessor::class,
        ),
    ],
    order: ['createdAt' => 'DESC']
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['username' => 'partial', 'email' => 'partial'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['username', 'email', 'createdAt'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private const string PREFIX_NAME = 'users-';

    public const array ROLES = [
        'super_admin' => 'ROLE_SUPER_ADMIN',
        'admin' => 'ROLE_ADMIN',
        'moder' => 'ROLE_MODERATEUR',
        'user' => 'ROLE_USER',
    ];

    public const array STATUS = [
        'INACTIVE' => 0,
        'ACTIVE' => 3,
        'BLOCKED' => 4,
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups([
        'user:read',
        'forum_category:col:forum-categories-full',
        'forum_theme:read',
        'forum_sujet:read',
        'forum_message:read',
        'gallery_image:read',
        'invest_portfolio:read',
        'jukebox_song:read',
        'news_article:read',
        'news_comment:read',
        'partner:read',
        'survey_question:read',
        'contact:col:read',
        'contact:item:read',
        'message_received:read',
        'message_sent:read',
    ])]
    protected UuidInterface $id;

    #[Groups(['user:read', 'user:admin'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $firstname = null;

    #[Groups(['user:read', 'user:admin'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $lastname = null;

    #[Groups([
        'user:read',
        'user:admin',
        'forum_category:col:forum-categories-full',
        'forum_theme:read',
        'forum_sujet:read',
        'forum_message:read',
        'gallery_image:read',
        'invest_portfolio:read',
        'jukebox_song:read',
        'news_article:read',
        'news_comment:read',
        'partner:read',
        'survey_question:read',
        'contact:col:read',
        'contact:item:read',
        'message_received:read',
        'message_sent:read',
    ])]
    #[ORM\Column(type: Types::STRING)]
    private string $username;

    #[Groups(['user:read', 'user:admin'])]
    #[ORM\Column(type: Types::STRING)]
    private string $email;

    #[ORM\Column(type: Types::STRING)]
    private string $password;

    #[Groups(['user:read', 'user:admin', 'news_comment:read', 'forum_sujet:item:read', 'forum_message:read'])]
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[Groups(['user:read', 'user:admin'])]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $status;

    #[ORM\Column(type: Types::JSON)]
    private array $security;

    #[ORM\Column(type: Types::JSON)]
    private array $activeEmail;

    #[ORM\Column(type: Types::JSON)]
    private array $resetPassword;

    #[ORM\Column(type: Types::JSON)]
    private array $preferences = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $avatarName = null;

    #[Vich\UploadableField(mapping: 'user_avatar_image', fileNameProperty: 'avatarName')]
    public ?File $avatarFile = null;

    #[Groups([
        'user:read',
        'contact:col:read',
        'contact:item:read',
        'news_comment:read',
        'forum_sujet:item:read',
        'forum_message:read',
    ])]
    public ?string $avatarUrl;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $avatarUpdatedAt = null;

    #[Groups([
        'user:read',
        'contact:col:read',
        'contact:item:read',
    ])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $lastVisit;

    #[Groups(['user:item:read'])]
    #[ORM\Column(type: Types::INTEGER)]
    private int $nbLogin = 0;

    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'user')]
    private Collection $addresses;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user')]
    private Collection $orders;

    #[Groups(['user:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    protected DateTimeInterface $createdAt;

    #[Groups(['user:item:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    protected DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->status = self::STATUS['INACTIVE'];
        $this->security = (new Security())->toArray();
        $this->activeEmail = (new ActiveEmail())->toArray();
        $this->resetPassword = (new ResetPassword())->toArray();
        $this->addresses = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->lastVisit = new DateTime();
    }

    public function __toString(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getFullname(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): ?string
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        if (empty($roles)) {
            $roles[] = self::ROLES['user'];
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSecurity(): Security
    {
        return Security::fromArray($this->security);
    }

    public function setSecurity(Security $security): self
    {
        $this->security = $security->toArray();

        return $this;
    }

    public function getActiveEmail(): ActiveEmail
    {
        return ActiveEmail::fromArray($this->activeEmail);
    }

    public function setActiveEmail(ActiveEmail $activeEmail): self
    {
        $this->activeEmail = $activeEmail->toArray();

        return $this;
    }

    public function getResetPassword(): ResetPassword
    {
        return ResetPassword::fromArray($this->resetPassword);
    }

    public function setResetPassword(ResetPassword $resetPassword): self
    {
        $this->resetPassword = $resetPassword->toArray();

        return $this;
    }

    public function getPreferences(): ?array
    {
        return $this->preferences;
    }

    public function setPreferences(array $preferences): self
    {
        $this->preferences = $preferences;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function setAvatarFile(?File $image = null): self
    {
        $this->avatarFile = $image;
        if ($image instanceof File) {
            // It is required that at least one field changes if you are using doctrine otherwise the event listeners won't be called and the file is lost.
            $this->avatarUpdatedAt = new DateTime();
        }

        return $this;
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarName(?string $avatarName): self
    {
        $this->avatarName = $avatarName;

        return $this;
    }

    public function getAvatarName(): ?string
    {
        return $this->avatarName;
    }

    public function getAvatarUpdatedAt(): ?DateTimeInterface
    {
        return $this->avatarUpdatedAt;
    }

    public function setAvatarUpdatedAt(?DateTimeInterface $date): self
    {
        $this->avatarUpdatedAt = $date;

        return $this;
    }

    public function getLastVisit(): DateTimeInterface
    {
        return $this->lastVisit;
    }

    public function setLastVisit(DateTimeInterface $lastVisit): self
    {
        $this->lastVisit = $lastVisit;

        return $this;
    }

    public function getNbLogin(): int
    {
        return $this->nbLogin;
    }

    public function setNbLogin(mixed $nbLogin): self
    {
        $this->nbLogin = $nbLogin;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return Address[]
     */
    public function getAddresses(): array
    {
        return $this->addresses->getValues();
    }

    public function addAddress(Address $address): self
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses[] = $address;
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        // set the owning side to null (unless already changed)
        if ($this->addresses->removeElement($address) && $address->getUser() === $this) {
            $address->setUser(null);
        }

        return $this;
    }

    /**
     * @return Order[]
     */
    public function getOrders(): array
    {
        return $this->orders->getValues();
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        // set the owning side to null (unless already changed)
        if ($this->orders->removeElement($order) && $order->getUser() === $this) {
            $order->setUser(null);
        }

        return $this;
    }

    public function getPreferredLang(): ?string
    {
        return $this->getPreferences()['lang'] ?? null;
    }
}
