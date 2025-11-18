<?php

namespace App\DataFixtures\dev\Shop;

use App\Entity\Shop\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public const int NB_LEVEL_1 = 2;

    public const int NB_LEVEL_2 = 4;

    public const int NB_LEVEL_3 = 8;

    public const int NB_LEVEL_4 = 16;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Générer les catégories racine (niveau 1)
        for ($i = 1; $i <= self::NB_LEVEL_1; ++$i) {
            $category = new Category();
            $category->setTitle($faker->company());
            $category->setDescription($faker->text());

            $this->addReference('shop_category_level_1_' . $i, $category);

            $manager->persist($category);
        }

        // Générer les catégories de niveau 2
        for ($i = 1; $i <= self::NB_LEVEL_2; ++$i) {
            $category = new Category();
            $category->setTitle($faker->company());
            $category->setDescription($faker->text());

            $parent = $this->getReference('shop_category_level_1_' . $faker->numberBetween(1, self::NB_LEVEL_1), Category::class);
            $category->setParent($parent);

            $this->addReference('shop_category_level_2_' . $i, $category);

            $manager->persist($category);
        }

        // Générer les catégories de niveau 3
        for ($i = 1; $i <= self::NB_LEVEL_3; ++$i) {
            $category = new Category();
            $category->setTitle($faker->company());
            $category->setDescription($faker->text());

            $parent = $this->getReference('shop_category_level_2_' . $faker->numberBetween(1, self::NB_LEVEL_2), Category::class);
            $category->setParent($parent);

            $this->addReference('shop_category_level_3_' . $i, $category);

            $manager->persist($category);
        }

        // Générer les catégories de niveau 4
        for ($i = 1; $i <= self::NB_LEVEL_4; ++$i) {
            $category = new Category();
            $category->setTitle($faker->company());
            $category->setDescription($faker->text());

            $parent = $this->getReference('shop_category_level_3_' . $faker->numberBetween(1, self::NB_LEVEL_3), Category::class);
            $category->setParent($parent);

            $this->addReference('shop_category_level_4_' . $i, $category);

            $manager->persist($category);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }
}
