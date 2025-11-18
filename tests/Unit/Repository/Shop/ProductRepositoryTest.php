<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository\Shop;

use App\Entity\Shop\Category;
use App\Entity\Shop\Product;
use App\Repository\Shop\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private ProductRepository $repo;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        /** @var ProductRepository $repo */
        $repo = $this->em->getRepository(Product::class);
        $this->repo = $repo;
    }

    public function testCountNbProductByCategory(): void
    {
        $categoryId = $this->em->getRepository(Category::class)->findOneBy([])->getId()->toString();

        $res = $this->repo
            ->countNbProductByCategory($categoryId);

        $this->assertIsNumeric($res);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->em->close();
    }
}
