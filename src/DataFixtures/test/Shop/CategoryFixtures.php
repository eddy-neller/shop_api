<?php

namespace App\DataFixtures\test\Shop;

use App\Entity\Shop\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public const int NB_LEVEL_1 = 4;

    public const int NB_LEVEL_2 = 8;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Générer les catégories racine (niveau 1)
        for ($i = 1; $i <= self::NB_LEVEL_1; ++$i) {
            $category = new Category();
            $category->setTitle('Shop category title ' . $i);
            $category->setDescription($faker->text());

            $this->addReference('shop_category_level_1_' . $i, $category);

            $manager->persist($category);
        }

        // Générer les catégories de niveau 2
        for ($i = 1; $i <= self::NB_LEVEL_2; ++$i) {
            $category = new Category();
            $category->setTitle('Shop category level 1 title ' . $i);
            $category->setDescription($faker->text());

            $parent = $this->getReference('shop_category_level_1_' . $faker->numberBetween(1, self::NB_LEVEL_1), Category::class);
            $category->setParent($parent);

            $this->addReference('shop_category_level_2_' . $i, $category);

            $manager->persist($category);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
