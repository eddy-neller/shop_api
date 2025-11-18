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
use App\Entity\User\User;
use App\Repository\Shop\CarrierRepository;
use App\Service\RouteRequirements;
use App\State\PaginatedCollectionProvider;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'shop_carrier')]
#[ORM\Entity(repositoryClass: CarrierRepository::class)]
#[ApiResource(
    shortName: 'ShopCarrier',
    operations: [
        new Get(
            uriTemplate: '/carriers/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            name: self::PREFIX_NAME . 'get',
        ),
        new Patch(
            uriTemplate: '/carriers/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            name: self::PREFIX_NAME . 'patch',
        ),
        new Delete(
            uriTemplate: '/carriers/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            name: self::PREFIX_NAME . 'delete',
        ),
        new Post(
            uriTemplate: '/carriers',
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            name: self::PREFIX_NAME . 'post',
        ),
        new GetCollection(
            uriTemplate: '/carriers',
            paginationClientItemsPerPage: true,
            name: self::PREFIX_NAME . 'col',
            provider: PaginatedCollectionProvider::class,
        ),
    ],
    routePrefix: '/shop',
    order: ['name' => 'ASC']
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['name' => 'partial', 'description' => 'partial'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['name', 'price', 'createdAt'])]
class Carrier
{
    private const string PREFIX_NAME = 'shop-carriers-';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['shop_carrier:read'])]
    protected UuidInterface $id;

    #[Groups(['shop_carrier:read', 'shop_carrier:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[Groups(['shop_carrier:read', 'shop_carrier:write'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $description;

    #[Groups(['shop_carrier:read', 'shop_carrier:write'])]
    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private float $price;

    #[Groups(['shop_carrier:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private DateTimeInterface $createdAt;

    #[Groups(['shop_carrier:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private DateTimeInterface $updatedAt;

    public function __toString(): string
    {
        return $this->getName() . ' - ' .
            number_format($this->getPrice() / 100, 2, ',', ',') . ' €[br]' .
            $this->getDescription();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

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
}
