<?php

namespace App\Infrastructure\DataFixtures\dev\Shop;

use App\Infrastructure\DataFixtures\DataFixturesTrait;
use App\Infrastructure\Entity\Shop\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Ramsey\Uuid\Uuid;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    use DataFixturesTrait;

    private AsciiSlugger $slugger;

    public const int NB_LEVEL_1 = 2;

    public const int NB_LEVEL_2 = 4;

    public const int NB_LEVEL_3 = 8;

    public const int NB_LEVEL_4 = 16;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Générer les catégories racine (niveau 1)
        for ($i = 1; $i <= self::NB_LEVEL_1; ++$i) {
            $category = new Category();
            $category->setId(Uuid::uuid4());
            $title = $faker->company();
            $category->setTitle($title);
            $category->setDescription($faker->text());
            $category->setSlug($this->generateSlug($title));

            $this->assignTimestamps($category);

            $this->addReference('shop_category_level_1_' . $i, $category);

            $manager->persist($category);
        }

        // Générer les catégories de niveau 2
        for ($i = 1; $i <= self::NB_LEVEL_2; ++$i) {
            $category = new Category();
            $category->setId(Uuid::uuid4());
            $title = $faker->company();
            $category->setTitle($title);
            $category->setDescription($faker->text());
            $category->setSlug($this->generateSlug($title));

            $parent = $this->getReference('shop_category_level_1_' . $faker->numberBetween(1, self::NB_LEVEL_1), Category::class);
            $category->setParent($parent);

            $this->assignTimestamps($category);

            $this->addReference('shop_category_level_2_' . $i, $category);

            $manager->persist($category);
        }

        // Générer les catégories de niveau 3
        for ($i = 1; $i <= self::NB_LEVEL_3; ++$i) {
            $category = new Category();
            $category->setId(Uuid::uuid4());
            $title = $faker->company();
            $category->setTitle($title);
            $category->setDescription($faker->text());
            $category->setSlug($this->generateSlug($title));

            $parent = $this->getReference('shop_category_level_2_' . $faker->numberBetween(1, self::NB_LEVEL_2), Category::class);
            $category->setParent($parent);

            $this->assignTimestamps($category);

            $this->addReference('shop_category_level_3_' . $i, $category);

            $manager->persist($category);
        }

        // Générer les catégories de niveau 4
        for ($i = 1; $i <= self::NB_LEVEL_4; ++$i) {
            $category = new Category();
            $category->setId(Uuid::uuid4());
            $title = $faker->company();
            $category->setTitle($title);
            $category->setDescription($faker->text());
            $category->setSlug($this->generateSlug($title));

            $parent = $this->getReference('shop_category_level_3_' . $faker->numberBetween(1, self::NB_LEVEL_3), Category::class);
            $category->setParent($parent);

            $this->assignTimestamps($category);

            $this->addReference('shop_category_level_4_' . $i, $category);

            $manager->persist($category);
        }

        $manager->flush();
    }

    private function assignTimestamps(Category $category): void
    {
        $timestamps = $this->generateTimestamps();
        $category->setCreatedAt($timestamps['createdAt']);
        $category->setUpdatedAt($timestamps['updatedAt']);
    }

    private function generateSlug(string $title): string
    {
        return $this->slugger->slug($title)->lower()->toString();
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }
}
