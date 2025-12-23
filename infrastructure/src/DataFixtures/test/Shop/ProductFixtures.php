<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\test\Shop;

use App\Infrastructure\DataFixtures\DataFixturesTrait;
use App\Infrastructure\Entity\Shop\Category;
use App\Infrastructure\Entity\Shop\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Ramsey\Uuid\Uuid;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ProductFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    use DataFixturesTrait;

    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $productsData = [
            [
                'title' => 'Product title 1',
                'subtitle' => 'Amazing product for everyone',
                'description' => 'This is a detailed description of our first product with all its features and benefits.',
                'price' => 2999,
            ],
            [
                'title' => 'Product title 2',
                'subtitle' => 'Premium quality product',
                'description' => 'High-end product with exceptional quality and durability for professional use.',
                'price' => 4999,
            ],
            [
                'title' => 'Product title 3',
                'subtitle' => 'Budget-friendly option',
                'description' => 'Affordable product without compromising on quality and performance.',
                'price' => 1999,
            ],
            [
                'title' => 'Product title 4',
                'subtitle' => 'Innovative design',
                'description' => 'Revolutionary product with cutting-edge technology and modern aesthetics.',
                'price' => 7999,
            ],
            [
                'title' => 'Product title 5',
                'subtitle' => 'Eco-friendly choice',
                'description' => 'Sustainable product made from environmentally friendly materials.',
                'price' => 3999,
            ],
            [
                'title' => 'Product title 6',
                'subtitle' => 'Versatile and practical',
                'description' => 'Multi-purpose product suitable for various applications and environments.',
                'price' => 5999,
            ],
            [
                'title' => 'Product title 7',
                'subtitle' => 'Compact and portable',
                'description' => 'Lightweight design perfect for travel and on-the-go use.',
                'price' => 3499,
            ],
            [
                'title' => 'Product title 8',
                'subtitle' => 'Professional grade',
                'description' => 'Industry-standard product trusted by professionals worldwide.',
                'price' => 9999,
            ],
            [
                'title' => 'Product title 9',
                'subtitle' => 'Limited edition',
                'description' => 'Exclusive product with unique features and premium packaging.',
                'price' => 14999,
            ],
            [
                'title' => 'Product title 10',
                'subtitle' => 'Best seller',
                'description' => 'Our most popular product loved by customers for its reliability and value.',
                'price' => 4499,
            ],
        ];

        foreach ($productsData as $productData) {
            $product = new Product();
            $product->setId(Uuid::uuid4());

            $title = $productData['title'];
            $product->setTitle($title);
            $product->setSlug($this->generateSlug($title));

            $product->setSubtitle($productData['subtitle']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);

            // Simuler qu'il y a une image
            $product->setImageName('product.jpg');

            // Assigner une catégorie aléatoire de niveau 2
            $categoryRef = 'shop_category_level_2_' . $faker->numberBetween(1, 2);
            $category = $this->getReference($categoryRef, Category::class);
            $product->setCategory($category);

            $timestamps = $this->generateTimestamps();
            $createdAt = $timestamps['createdAt'];
            $product->setCreatedAt($createdAt);
            $product->setUpdatedAt($timestamps['updatedAt']);

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
        return ['test'];
    }

    private function updateStats(ObjectManager $manager): void
    {
        $categories = $manager->getRepository(Category::class)->findAll();

        foreach ($categories as $category) {
            $nbProductFound = (int) $manager->getRepository(Product::class)->countNbProductByCategory($category->getId()->toString());

            $category->setNbProduct($nbProductFound);
        }

        $manager->flush();
    }

    private function generateSlug(string $title): string
    {
        return $this->slugger->slug($title)->lower()->toString();
    }
}
