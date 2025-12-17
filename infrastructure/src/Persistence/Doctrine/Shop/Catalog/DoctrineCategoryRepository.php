<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Shop\Catalog;

use App\Application\Shared\Port\UuidGeneratorInterface;
use App\Application\Shop\Port\CategoryRepositoryInterface;
use App\Application\Shop\ReadModel\CategoryTree;
use App\Domain\Shop\Catalog\Model\Category as DomainCategory;
use App\Domain\Shop\Catalog\ValueObject\CategoryId;
use App\Infrastructure\Entity\Shop\Category as DoctrineCategory;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @codeCoverageIgnore
 */
final readonly class DoctrineCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UuidGeneratorInterface $uuidGenerator,
        private CategoryMapper $mapper,
    ) {
    }

    public function nextIdentity(): CategoryId
    {
        return CategoryId::fromString($this->uuidGenerator->generate());
    }

    public function save(DomainCategory $category): void
    {
        $entity = $this->findEntity($category->getId());
        $entity = $this->mapper->toDoctrine($category, $entity);

        $parentId = $category->getParentId();
        $entity->setParent(null === $parentId ? null : $this->findEntity($parentId));

        $this->em->persist($entity);
        $this->em->flush();
    }

    public function delete(DomainCategory $category): void
    {
        $entity = $this->findEntity($category->getId());
        if (null === $entity) {
            return;
        }

        $this->em->remove($entity);
        $this->em->flush();
    }

    public function findById(CategoryId $id): ?DomainCategory
    {
        $entity = $this->findEntity($id);

        return null === $entity ? null : $this->mapper->toDomain($entity);
    }

    public function findTreeById(CategoryId $id): ?CategoryTree
    {
        $entity = $this->findEntity($id);
        if (null === $entity) {
            return null;
        }

        $parentEntity = $entity->getParent();
        $childrenEntities = $entity->getChildren();

        $parent = null === $parentEntity ? null : $this->mapper->toDomain($parentEntity);
        $children = array_map(fn (DoctrineCategory $child): DomainCategory => $this->mapper->toDomain($child), $childrenEntities);

        return new CategoryTree(
            category: $this->mapper->toDomain($entity),
            parent: $parent,
            children: $children,
        );
    }

    private function findEntity(CategoryId $id): ?DoctrineCategory
    {
        $repository = $this->em->getRepository(DoctrineCategory::class);
        if (!$repository instanceof NestedTreeRepository) {
            return null;
        }

        $entity = $repository->find($id->toString());

        return $entity instanceof DoctrineCategory ? $entity : null;
    }
}
