<?php

namespace App\Infrastructure\DataFixtures\test\Shop;

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

    public const int NB_LEVEL_1 = 4;

    public const int NB_LEVEL_2 = 8;

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
            $title = 'Shop category title ' . $i;
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
            $title = 'Shop category level 1 title ' . $i;
            $category->setTitle($title);
            $category->setDescription($faker->text());
            $category->setSlug($this->generateSlug($title));

            $parent = $this->getReference('shop_category_level_1_' . $faker->numberBetween(1, self::NB_LEVEL_1), Category::class);
            $category->setParent($parent);

            $this->assignTimestamps($category);

            $this->addReference('shop_category_level_2_' . $i, $category);

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
        return ['test'];
    }
}
