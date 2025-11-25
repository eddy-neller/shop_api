<?php

namespace App\DataFixtures\dev\Shop;

use App\Entity\Shop\Category;
use App\Entity\Shop\Product;
use App\Infrastructure\DataFixtures\DataFixturesTrait;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    use DataFixturesTrait;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $products = [
            0 => [
                'title' => 'Bonnet rouge',
                'slug' => 'bonnet-rouge',
                'subtitle' => "Le bonnet parfait pour l'hiver",
                'description' => $faker->realText(),
                'price' => 900,
                'imageName' => 'bonnet1.jpg',
            ],
            1 => [
                'title' => 'Le Bonnet du skieur',
                'slug' => 'le-bonnet-du-skieur',
                'subtitle' => 'Le bonnet parfait pour le ski',
                'description' => $faker->realText(),
                'price' => 1200,
                'imageName' => 'bonnet2.jpg',
            ],
            2 => [
                'title' => 'L\'écharpe du lover',
                'slug' => 'l-echarpe-du-lover',
                'subtitle' => 'L\'écharpe parfait pour les soirées romantiques',
                'description' => $faker->realText(),
                'price' => 1900,
                'imageName' => 'echarpe1.jpg',
            ],
            3 => [
                'title' => 'L\'écharpe du samedi soir',
                'slug' => 'l-echarpe-du-samedi-soir',
                'subtitle' => 'L\'écharpe parfait pour vos week-ends',
                'description' => $faker->realText(),
                'price' => 1400,
                'imageName' => 'echarpe2.jpg',
            ],
            4 => [
                'title' => 'Le manteau de soirée',
                'slug' => 'le-manteau-de-soirée',
                'subtitle' => 'Le manteau martiniquais pour vos soirées',
                'description' => $faker->realText(),
                'price' => 6900,
                'imageName' => 'manteau1.jpg',
            ],
            5 => [
                'title' => 'Le manteau famille',
                'slug' => 'le-manteau-famille',
                'subtitle' => 'Le manteau pour vos sorties en famille',
                'description' => $faker->realText(),
                'price' => 7990,
                'imageName' => 'manteau2.jpg',
            ],
            6 => [
                'title' => 'Le T-Shirt manche longue',
                'slug' => 'le-t-shirt-manche-longue',
                'subtitle' => 'Le T-Shirt taillé pour les hommes',
                'description' => $faker->realText(),
                'price' => 1490,
                'imageName' => 'tshirt2.jpg',
            ],
            7 => [
                'title' => 'Le T-Shirt basique',
                'slug' => 'le-t-shirt-basique',
                'subtitle' => 'Le T-Shirt basique parfait pour les hommes',
                'description' => $faker->realText(),
                'price' => 990,
                'imageName' => 'tshirt1.jpg',
            ],
        ];

        foreach ($products as $value) {
            $product = new Product();
            $product->setTitle($value['title']);
            $product->setSlug($value['slug']);
            $product->setSubtitle($value['subtitle']);
            $product->setDescription($value['description']);
            $product->setPrice($value['price']);
            $product->setImageName($value['imageName']);

            $categoryLevel = $faker->numberBetween(1, 4);
            $categoryReference = 'shop_category_level_' . $categoryLevel . '_' . $faker->numberBetween(1, constant(CategoryFixtures::class . '::NB_LEVEL_' . $categoryLevel));
            $category = $this->getReference($categoryReference, Category::class);
            $product->setCategory($category);

            $manager->persist($product);
        }

        $manager->flush();

        $this->updateStats($manager);
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }

    private function updateStats(ObjectManager $manager): void
    {
        $categories = $manager->getRepository(Category::class)->findAll();

        foreach ($categories as $category) {
            $nbProductFound = (int) $manager->getRepository(Product::class)->countNbProductByCategory($category->getId());

            $category->setNbProduct($nbProductFound);
        }

        $manager->flush();
    }
}
