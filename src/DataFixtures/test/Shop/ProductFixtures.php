<?php

namespace App\DataFixtures\test\Shop;

use App\DataFixtures\DataFixturesTrait;
use App\Entity\Shop\Category;
use App\Entity\Shop\Product;
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

        $productsData = [
            [
                'title' => 'Product title 1',
                'subtitle' => 'Amazing product for everyone',
                'description' => 'This is a detailed description of our first product with all its features and benefits.',
                'price' => 29.99,
                'slug' => 'product-title-1',
            ],
            [
                'title' => 'Product title 2',
                'subtitle' => 'Premium quality product',
                'description' => 'High-end product with exceptional quality and durability for professional use.',
                'price' => 49.99,
                'slug' => 'product-title-2',
            ],
            [
                'title' => 'Product title 3',
                'subtitle' => 'Budget-friendly option',
                'description' => 'Affordable product without compromising on quality and performance.',
                'price' => 19.99,
                'slug' => 'product-title-3',
            ],
            [
                'title' => 'Product title 4',
                'subtitle' => 'Innovative design',
                'description' => 'Revolutionary product with cutting-edge technology and modern aesthetics.',
                'price' => 79.99,
                'slug' => 'product-title-4',
            ],
            [
                'title' => 'Product title 5',
                'subtitle' => 'Eco-friendly choice',
                'description' => 'Sustainable product made from environmentally friendly materials.',
                'price' => 39.99,
                'slug' => 'product-title-5',
            ],
            [
                'title' => 'Product title 6',
                'subtitle' => 'Versatile and practical',
                'description' => 'Multi-purpose product suitable for various applications and environments.',
                'price' => 59.99,
                'slug' => 'product-title-6',
            ],
            [
                'title' => 'Product title 7',
                'subtitle' => 'Compact and portable',
                'description' => 'Lightweight design perfect for travel and on-the-go use.',
                'price' => 34.99,
                'slug' => 'product-title-7',
            ],
            [
                'title' => 'Product title 8',
                'subtitle' => 'Professional grade',
                'description' => 'Industry-standard product trusted by professionals worldwide.',
                'price' => 99.99,
                'slug' => 'product-title-8',
            ],
            [
                'title' => 'Product title 9',
                'subtitle' => 'Limited edition',
                'description' => 'Exclusive product with unique features and premium packaging.',
                'price' => 149.99,
                'slug' => 'product-title-9',
            ],
            [
                'title' => 'Product title 10',
                'subtitle' => 'Best seller',
                'description' => 'Our most popular product loved by customers for its reliability and value.',
                'price' => 44.99,
                'slug' => 'product-title-10',
            ],
        ];

        foreach ($productsData as $productData) {
            $product = new Product();
            $product->setTitle($productData['title']);
            $product->setSubtitle($productData['subtitle']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setSlug($productData['slug']);

            // Assigner une catégorie aléatoire de niveau 2
            $categoryRef = 'shop_category_level_2_' . $faker->numberBetween(1, CategoryFixtures::NB_LEVEL_2);
            $category = $this->getReference($categoryRef, Category::class);
            $product->setCategory($category);

            $timestamps = $this->generateTimestamps();
            $createdAt = $timestamps['createdAt'];
            $product->setCreatedAt($createdAt);
            $product->setUpdatedAt($timestamps['updatedAt']);

            // Simuler qu'il y a une image
            $product->setImageName('product.jpg');
            $product->setImageUpdatedAt($faker->dateTimeBetween($createdAt));

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
