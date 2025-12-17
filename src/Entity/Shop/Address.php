<?php

namespace App\Entity\Shop;

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
use App\Entity\HasOwnerInterface;
use App\Infrastructure\Entity\User\User;
use App\Presentation\RouteRequirements;
use App\Presentation\Shared\State\PaginatedCollectionProvider;
use App\Repository\Shop\AddressRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'shop_address')]
#[ORM\Index(name: 'ShopAddressUserIdx', columns: ['user_id'])]
#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ApiResource(
    shortName: 'ShopAddress',
    operations: [
        new Get(
            uriTemplate: '/addresses/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('shop_address:item:read', object)",
            name: self::PREFIX_NAME . 'me-get',
        ),
        new Patch(
            uriTemplate: '/addresses/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('shop_address:item:write', object)",
            name: self::PREFIX_NAME . 'me-patch',
        ),
        new Delete(
            uriTemplate: '/addresses/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('shop_address:item:write', object)",
            name: self::PREFIX_NAME . 'me-delete',
        ),
        new Post(
            uriTemplate: '/addresses',
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::PREFIX_NAME . 'me-post',
        ),
        new GetCollection(
            uriTemplate: '/addresses',
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            paginationClientItemsPerPage: true,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::PREFIX_NAME . 'me-col',
            provider: PaginatedCollectionProvider::class,
        ),
    ],
    routePrefix: '/shop/me',
    order: ['createdAt' => 'DESC']
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['name' => 'partial', 'city' => 'partial', 'country' => 'exact'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['name', 'city', 'country', 'createdAt'])]
class Address implements HasOwnerInterface
{
    private const string PREFIX_NAME = 'shop-addresses-';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['shop_address:read'])]
    protected UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'addresses')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?User $user = null;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'The name must be at least {{ limit }} characters long.',
        maxMessage: 'The name must be at most {{ limit }} characters long.'
    )]
    private string $name;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 32,
        minMessage: 'The firstname must be at least {{ limit }} characters long.',
        maxMessage: 'The firstname must be at most {{ limit }} characters long.'
    )]
    private string $firstname;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 32,
        minMessage: 'The lastname must be at least {{ limit }} characters long.',
        maxMessage: 'The lastname must be at most {{ limit }} characters long.'
    )]
    private string $lastname;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'The company must be at least {{ limit }} characters long.',
        maxMessage: 'The company must be at most {{ limit }} characters long.'
    )]
    private ?string $company;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 150,
        minMessage: 'The address must be at least {{ limit }} characters long.',
        maxMessage: 'The address must be at most {{ limit }} characters long.'
    )]
    private string $address;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 30,
        minMessage: 'The zipcode must be at least {{ limit }} characters long.',
        maxMessage: 'The zipcode must be at most {{ limit }} characters long.'
    )]
    private string $zip;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'The city must be at least {{ limit }} characters long.',
        maxMessage: 'The city must be at most {{ limit }} characters long.'
    )]
    private string $city;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'The country must be at least {{ limit }} characters long.',
        maxMessage: 'The country must be at most {{ limit }} characters long.'
    )]
    private string $country;

    #[Groups(['shop_address:read', 'shop_address:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 30,
        minMessage: 'The phone must be at least {{ limit }} characters long.',
        maxMessage: 'The phone must be at most {{ limit }} characters long.'
    )]
    private string $phone;

    #[Groups(['shop_address:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    #[ApiFilter(filterClass: OrderFilter::class)]
    protected DateTimeImmutable $createdAt;

    #[Groups(['shop_address:item:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    protected DateTimeImmutable $updatedAt;

    public function __toString(): string
    {
        return $this->getName() . '[br]' . $this->getAddress() . '[br]' .
            $this->getZip() . ' - ' . $this->getCountry();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
