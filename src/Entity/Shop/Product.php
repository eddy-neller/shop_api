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
use App\Repository\Shop\ProductRepository;
use App\Security\Validator\Constraints as AppAssert;
use App\Service\RouteRequirements;
use App\State\PaginatedCollectionProvider;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Table(name: 'shop_product')]
#[ORM\Index(name: 'ShopProductCategoryIdx', columns: ['category_id'])]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    shortName: 'ShopProduct',
    operations: [
        new Get(
            uriTemplate: '/products/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            name: self::PREFIX_NAME . 'get',
        ),
        new Patch(
            uriTemplate: '/products/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            name: self::PREFIX_NAME . 'patch',
        ),
        new Delete(
            uriTemplate: '/products/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            name: self::PREFIX_NAME . 'delete',
        ),
        new Post(
            uriTemplate: '/products',
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            name: self::PREFIX_NAME . 'post',
        ),
        new GetCollection(
            uriTemplate: '/products',
            paginationClientItemsPerPage: true,
            name: self::PREFIX_NAME . 'col',
            provider: PaginatedCollectionProvider::class,
        ),
        new Post(
            uriTemplate: '/products/{id}/image',
            inputFormats: ['multipart' => ['multipart/form-data']],
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                summary: 'Creates the Image of a Shop Product resource',
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . User::ROLES['admin'] . "')",
            validationContext: ['groups' => ['Default', 'Image']],
            name: self::PREFIX_NAME . 'image',
        ),
    ],
    routePrefix: '/shop',
    order: ['createdAt' => 'DESC']
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['title' => 'partial', 'description' => 'partial'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['title', 'price', 'createdAt'])]
class Product
{
    private const string PREFIX_NAME = 'shop-products-';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['shop_product:read'])]
    protected UuidInterface $id;

    #[Groups(['shop_product:read', 'shop_product:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[AppAssert\Shop\ShopProductNotExists]
    #[Assert\Length(max: 255)]
    private string $title;

    #[Groups(['shop_product:read', 'shop_product:write'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $subtitle;

    #[Groups(['shop_product:read', 'shop_product:write'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $description;

    #[Groups(['shop_product:read', 'shop_product:write'])]
    #[ORM\Column(type: Types::FLOAT, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private float $price;

    #[Groups(['shop_product:read', 'shop_product:write'])]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $slug;

    #[Groups(['shop_product:read'])]
    public ?string $imageUrl;

    #[Groups(['shop_product:write'])]
    #[Assert\NotBlank(groups: ['Image'])]
    #[Assert\File(maxSize: '2M', mimeTypes: ['image/png', 'image/gif', 'image/jpeg', 'image/pjpeg'], groups: ['Image'])]
    #[Assert\Image(minWidth: 200, maxWidth: 2000, maxHeight: 2000, minHeight: 200, groups: ['Image'])]
    #[Vich\UploadableField(mapping: 'shop_product_image', fileNameProperty: 'imageName')]
    public ?File $imageFile = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $imageUpdatedAt = null;

    #[Groups(['shop_product:read', 'shop_product:write'])]
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[Groups(['shop_product:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private DateTimeInterface $createdAt;

    #[Groups(['shop_product:item:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private DateTimeInterface $updatedAt;

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function setImageFile(?File $image = null): self
    {
        $this->imageFile = $image;
        if ($image instanceof File) {
            $this->imageUpdatedAt = new DateTime();
        }

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function getImageUpdatedAt(): ?DateTimeInterface
    {
        return $this->imageUpdatedAt;
    }

    public function setImageUpdatedAt(DateTimeInterface $date): self
    {
        $this->imageUpdatedAt = $date;

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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
