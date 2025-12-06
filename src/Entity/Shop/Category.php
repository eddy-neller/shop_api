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
use App\Domain\User\Security\ValueObject\RoleSet;
use App\Presentation\RouteRequirements;
use App\Repository\NestedTreeRepository;
use App\Security\Validator\Constraints as AppAssert;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Traits\NestedSetEntityUuid;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'shop_category')]
#[ORM\Index(name: 'ShopCategoryParentIdx', columns: ['parent_id'])]
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[Gedmo\Tree(type: 'nested')]
#[ApiResource(
    shortName: 'ShopCategory',
    operations: [
        new Get(
            uriTemplate: '/categories/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            name: self::PREFIX_NAME . 'get',
        ),
        new Patch(
            uriTemplate: '/categories/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . RoleSet::ROLE_ADMIN . "')",
            name: self::PREFIX_NAME . 'patch',
        ),
        new Delete(
            uriTemplate: '/categories/{id}',
            requirements: ['id' => RouteRequirements::UUID],
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . RoleSet::ROLE_ADMIN . "')",
            name: self::PREFIX_NAME . 'delete',
        ),
        new GetCollection(
            uriTemplate: '/categories',
            openapi: new Model\Operation(
                parameters: [
                    new Model\Parameter(
                        name: 'level',
                        in: 'query',
                        required: true,
                        schema: [
                            'type' => 'integer',
                        ],
                    ),
                ]
            ),
            name: self::PREFIX_NAME . 'col',
        ),
        new Post(
            uriTemplate: '/categories',
            openapi: new Model\Operation(
                security: [['ApiKeyAuth' => []]]
            ),
            security: "is_granted('" . RoleSet::ROLE_ADMIN . "')",
            name: self::PREFIX_NAME . 'post',
        ),
    ],
    routePrefix: '/shop',
    paginationEnabled: false
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['level' => 'exact'])]
class Category
{
    use NestedSetEntityUuid;

    private const string PREFIX_NAME = 'shop-categories-';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['shop_category:read', 'shop_product:read'])]
    protected UuidInterface $id;

    #[Groups(['shop_category:read', 'shop_category:write', 'shop_product:read'])]
    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    #[AppAssert\Shop\ShopCategoryNotExists]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'The title must be at least {{ limit }} characters long.',
        maxMessage: 'The title must be at most {{ limit }} characters long.'
    )]
    #[ApiFilter(filterClass: OrderFilter::class)]
    private string $title;

    #[Groups(['shop_category:item:read', 'shop_category:write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 1000,
        minMessage: 'The description must be at least {{ limit }} characters long.',
        maxMessage: 'The description must be at most {{ limit }} characters long.'
    )]
    private ?string $description = null;

    #[Groups(['shop_category:read'])]
    #[ORM\Column(type: Types::INTEGER)]
    private int $nbProduct = 0;

    #[Groups(['shop_category:read'])]
    #[ORM\Column(length: 128, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    private string $slug;

    #[Groups(['shop_category:read', 'shop_category:write'])]
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    #[MaxDepth(1)]
    private ?Category $parent;

    #[Groups(['shop_category:read'])]
    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['left' => 'ASC'])]
    #[MaxDepth(1)]
    private Collection $children;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
    private Collection $products;

    #[Groups(['shop_category:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    #[ApiFilter(filterClass: OrderFilter::class)]
    private DateTimeInterface $createdAt;

    #[Groups(['shop_category:item:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getNbProduct(): int
    {
        return $this->nbProduct;
    }

    public function setNbProduct(int $nbProduct): self
    {
        $this->nbProduct = $nbProduct;

        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return Category[]
     */
    public function getChildren(): array
    {
        return $this->children->getValues();
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products->getValues();
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

    public function increaseNbProduct(): void
    {
        ++$this->nbProduct;
    }

    public function decreaseNbProduct(): void
    {
        --$this->nbProduct;
    }
}
